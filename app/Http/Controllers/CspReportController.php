<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * CspReportController — receives CSP violation reports from the browser.
 *
 * Browsers POST application/csp-report (legacy) or application/reports+json
 * (newer Reporting API) to this endpoint when a request would violate our
 * Content-Security-Policy-Report-Only policy. We log them to a dedicated
 * channel for analysis during the inline-handler migration.
 *
 * Rate-limited to avoid a misbehaving browser or pentester flooding logs.
 */
class CspReportController extends Controller
{
    public function store(Request $request)
    {
        $report = $request->all();

        // Shrink the log footprint — keep the directives/source that broke,
        // drop long referrer/document-uri strings to prefix + trailing 40.
        $summary = [
            'directive' => data_get($report, 'csp-report.violated-directive',
                           data_get($report, 'body.effectiveDirective')),
            'blocked'   => data_get($report, 'csp-report.blocked-uri',
                           data_get($report, 'body.blockedURL')),
            'source'    => $this->trim(data_get($report, 'csp-report.source-file',
                           data_get($report, 'body.sourceFile'))),
            'document'  => $this->trim(data_get($report, 'csp-report.document-uri',
                           data_get($report, 'body.documentURL'))),
            'line'      => data_get($report, 'csp-report.line-number',
                           data_get($report, 'body.lineNumber')),
            'sample'    => $this->trim(data_get($report, 'csp-report.script-sample',
                           data_get($report, 'body.sample')), 160),
        ];

        Log::channel('single')->info('csp.violation', $summary);

        return response()->noContent();
    }

    private function trim(?string $s, int $max = 120): ?string
    {
        if ($s === null) return null;
        return strlen($s) <= $max ? $s : '…' . substr($s, -$max);
    }
}
