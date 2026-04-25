{{-- ── NEWS & ANNOUNCEMENTS WIDGET ──────────────────────────────────────── --}}
@include('partials.dashboard-widgets-style')

@php $annCount = $latestAnnouncements->count(); @endphp

<div class="section-header">
    <h6>News &amp; Announcements</h6>
</div>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card dash-widget" style="min-height:auto;">
            <div class="widget-header">
                <div class="d-flex align-items-center gap-3">
                    <div class="widget-icon"><i class="bi bi-megaphone-fill"></i></div>
                    <div>
                        <div class="widget-number">{{ $annCount }}</div>
                        <div class="widget-label">Latest {{ $annCount === 1 ? 'Announcement' : 'Announcements' }}</div>
                    </div>
                </div>
            </div>
            <div class="widget-body">
                @forelse($latestAnnouncements as $ann)
                @php
                    $attachments = $ann->attachment_paths ?? [];
                    $imageExts   = ['jpg','jpeg','png','gif','webp'];
                @endphp
                <div class="{{ !$loop->last ? 'pb-3 mb-3' : '' }}" style="{{ !$loop->last ? 'border-bottom:1px solid var(--line-soft);' : '' }}">
                    <div class="d-flex align-items-start gap-3">
                        <div style="width:40px;height:40px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="bi bi-megaphone-fill" style="font-size:16px;color:var(--primary-dark);"></i>
                        </div>
                        <div class="flex-fill" style="min-width:0;">

                            {{-- Title + company badges --}}
                            <div class="d-flex align-items-start gap-2 flex-wrap">
                                <span style="font-family:var(--sans);font-weight:600;font-size:14px;color:var(--ink);letter-spacing:-0.005em;">{{ $ann->title }}</span>
                                @if(!empty($ann->companies))
                                    @foreach($ann->companies as $co)
                                        <span style="font-family:var(--mono);font-size:9.5px;font-weight:500;padding:2px 8px;border-radius:999px;background:var(--primary-tint);color:var(--primary-dark);border:1px solid rgba(17,118,106,0.14);text-transform:uppercase;letter-spacing:.1em;">{{ $co }}</span>
                                    @endforeach
                                @endif
                            </div>

                            {{-- Full message body --}}
                            @if($ann->body)
                            <div class="mt-1" style="font-family:var(--sans);font-size:13px;line-height:1.6;color:var(--ink-2);white-space:pre-line;">{{ $ann->body }}</div>
                            @endif

                            {{-- Attachments — images inline, PDFs as styled link --}}
                            @if(!empty($attachments))
                            <div class="mt-2">
                                @foreach($attachments as $i => $path)
                                @php $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION)); @endphp
                                @if(in_array($ext, $imageExts))
                                    <a href="{{ asset('storage/'.$path) }}" target="_blank" style="display:inline-block;margin:0 6px 6px 0;">
                                        <img src="{{ asset('storage/'.$path) }}" alt="Attachment {{ $i+1 }}"
                                             style="max-width:100%;max-height:280px;border-radius:14px;border:1px solid var(--line-soft);object-fit:contain;display:block;box-shadow:0 1px 2px rgba(15,26,29,0.06),0 8px 20px -6px rgba(15,26,29,0.18);">
                                    </a>
                                @else
                                    <a href="{{ asset('storage/'.$path) }}" target="_blank"
                                       class="d-inline-flex align-items-center gap-2 px-3 py-2 mb-2"
                                       style="font-family:var(--sans);font-size:13px;color:var(--danger);text-decoration:none;background:var(--surface);border:1px solid var(--line);border-radius:999px;">
                                        <i class="bi bi-file-earmark-pdf-fill" style="font-size:16px;"></i>
                                        <span>PDF Attachment {{ $i+1 }}</span>
                                        <i class="bi bi-arrow-up-right" style="font-size:11px;opacity:0.6;"></i>
                                    </a>
                                @endif
                                @endforeach
                            </div>
                            @endif

                            <div class="mt-2" style="font-family:var(--mono);font-size:10px;color:var(--mute);text-transform:uppercase;letter-spacing:.12em;">
                                {{ $ann->created_at->format('d/m/Y') }}
                                &middot; {{ $ann->creator?->employee?->full_name ?? $ann->creator?->name ?? 'HR' }}
                            </div>

                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-4">
                    <div style="width:44px;height:44px;background:var(--primary-tint);border:1px solid rgba(17,118,106,0.14);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="bi bi-megaphone" style="font-size:18px;color:var(--primary-dark);"></i>
                    </div>
                    <div style="font-family:var(--sans);font-size:13px;color:var(--mute);">No announcements at the moment.</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
