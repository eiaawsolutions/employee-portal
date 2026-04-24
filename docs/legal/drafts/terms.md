# EIAAW Workforce — Terms of Service (drafting outline for counsel)

**Status:** pre-launch outline. NOT binding. Counsel converts this into the
signed Terms before public launch. Flag every `COUNSEL:` marker as a point
where we're aware we need legal judgement.

**Jurisdiction:** Malaysia (drafting party), with specific accommodations
for APAC + EU customers at Enterprise tier.

---

## 1. Parties and definitions

1.1 **"EIAAW"** means EIAAW Solutions Sdn. Bhd., a company incorporated in Malaysia (Company No. COUNSEL: insert).

1.2 **"Customer"** means the legal entity that signs up for or uses an EIAAW Workforce workspace. The individual accepting these Terms warrants authority to bind the Customer.

1.3 **"Service"** means the EIAAW Workforce SaaS platform accessed at `ep.eiaawsolutions.com` and its tenant subdomains, including the Core HR, Payroll, Claims, IT Asset Inventory, Accounting, and AI Assistant modules as enabled for the Customer's subscription tier.

1.4 **"Workspace"** means the isolated Customer environment provisioned by EIAAW, identified by a unique subdomain (e.g. `acme.ep.eiaawsolutions.com`).

1.5 **"Authorised User"** means any natural person the Customer permits to access the Workspace, including employees, contractors, and auditors. The Customer is responsible for Authorised Users' compliance with these Terms.

1.6 **"Subscription"** means the paid plan the Customer has selected (Starter, Growth, Scale, or Enterprise), its billing interval, and its currency.

## 2. Access and licence

2.1 Subject to these Terms and timely payment of Fees, EIAAW grants the Customer a non-exclusive, non-transferable, revocable right to access and use the Service for its internal business operations during the subscription term.

2.2 The Customer shall not (a) reverse-engineer the Service, (b) resell or white-label the Service without a written agreement, (c) use the Service to build a competing product, (d) scrape or bulk-extract data beyond the export paths the Service exposes, or (e) attempt to bypass tenant-isolation, rate-limiting, or billing enforcement.

2.3 COUNSEL: decide whether to include a clause on "benchmarking" use, per industry norm.

## 3. Subscription, fees, and trial

3.1 **Tiers and fees.** The Customer pays the per-active-employee-per-month fee for its tier, as published at `ep.eiaawsolutions.com/pricing` and captured at the time of sign-up. MYR is the primary billing currency; USD is available on request.

3.2 **Trial.** New workspaces receive a 14-day Growth-tier trial without credit card. On day 15, EIAAW auto-converts the workspace to the Starter tier unless the Customer upgrades or adds a payment method.

3.3 **Active-employee metric.** Only employees with an active record on the day of the billing run count toward Fees. Invited-but-not-started, terminated, and deactivated records do not count.

3.4 **Billing cadence.** Monthly billing renews on the anniversary of the sign-up date; annual billing gets two months free (pay ten months, receive twelve).

3.5 **Late payment.** Missed payments move the Subscription to `past_due`. After a 3-day grace period the Workspace is suspended. Data is retained but read-only until a successful payment clears the flag. COUNSEL: confirm grace period aligns with Malaysian consumer-contract norms.

3.6 **Price changes.** EIAAW may adjust the published rate card with at least 60 days' notice to affected Customers. Existing annual Subscriptions are honoured at the current price through the end of their term.

3.7 **Tax.** Fees exclude SST, GST, VAT, and equivalent. The Customer is responsible for any applicable taxes except those based on EIAAW's net income.

## 4. Customer Data and AI

4.1 **Ownership.** The Customer retains all right, title, and interest in the data submitted to the Workspace ("Customer Data"). EIAAW processes Customer Data as a processor under the Data Processing Agreement.

4.2 **AI Assistant.** The AI Assistant is retrieval-grounded on the Customer's Workspace only. EIAAW does NOT use Customer Data to train or fine-tune any model (owned or third-party). The third-party LLM providers EIAAW uses are listed in the DPA, and their APIs do not train on API-submitted data by default.

4.3 **AI accuracy.** The AI Assistant is provided on an "as is" basis. Outputs should be reviewed before action. EIAAW is not liable for decisions made solely on AI-generated content.

4.4 **Export.** The Customer may export Customer Data at any time via the admin console (CSV) or the audit export command (JSONL, Scale tier and above).

## 5. Acceptable use

5.1 The Customer and its Authorised Users shall not use the Service to store or transmit:
- Malware, phishing content, or material that violates applicable law
- Personal data of data subjects who have not been informed of the processing
- Content that infringes third-party intellectual property rights
- Highly sensitive categories (children's data, political affiliation, biometric data) beyond what is strictly required for lawful HR purposes and disclosed under PDPA/GDPR

5.2 EIAAW may suspend or terminate a Workspace for material breach of 5.1 without refund. COUNSEL: confirm timeframe for notice and cure where reasonable.

## 6. Service levels (Enterprise only)

6.1 Enterprise Customers receive a 99.9% monthly uptime SLA. Service credits apply per the Enterprise Addendum.

6.2 Starter, Growth, and Scale tiers are provided without a contractual uptime SLA, though EIAAW pursues the same operational targets.

## 7. Security and confidentiality

7.1 EIAAW applies the security controls described at `ep.eiaawsolutions.com/security`, which include Postgres Row-Level Security tenant isolation, encryption at rest and in transit, an HMAC-chained audit log, and MFA support.

7.2 EIAAW notifies the Customer without undue delay (and within 72 hours for EU data subjects) of any Personal Data Breach affecting the Customer's Workspace.

7.3 Each party shall keep the other's Confidential Information confidential using the standard of care it applies to its own confidential information, and for no less than the duration of the Subscription plus three years.

## 8. Warranties, disclaimers, and limits

8.1 EIAAW warrants that the Service will materially conform to its published documentation.

8.2 EXCEPT AS EXPRESSLY STATED, THE SERVICE IS PROVIDED "AS IS." EIAAW DISCLAIMS ALL IMPLIED WARRANTIES, INCLUDING MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE, TO THE MAXIMUM EXTENT PERMITTED BY LAW.

8.3 **Liability cap.** EIAAW's aggregate liability under these Terms is capped at the Fees paid by the Customer in the 12 months preceding the event giving rise to the claim. COUNSEL: decide whether to keep the 12-month cap, confirm carve-outs for gross negligence / wilful misconduct / data-protection breaches.

8.4 Neither party is liable for indirect, consequential, or punitive damages (lost profits, lost revenue, lost goodwill).

## 9. Term and termination

9.1 These Terms commence on the Customer's first use of the Service and continue until the Subscription is terminated.

9.2 Either party may terminate for material breach uncured 30 days after written notice. The Customer may additionally terminate at any time via the admin console, effective at the end of the current billing period.

9.3 On termination:
- Customer Data goes read-only for 30 days
- EIAAW deletes Customer Data from primary storage after 30 days and purges encrypted backups within 90 days
- EIAAW refunds any prepaid, unused Fees on annual plans pro-rata, except in the case of termination for Customer breach

## 10. Governing law and disputes

10.1 These Terms are governed by the laws of Malaysia.

10.2 Disputes shall first be escalated to the Customer's and EIAAW's respective senior legal counsel. Unresolved disputes shall be referred to the Asian International Arbitration Centre (AIAC) in Kuala Lumpur under its rules, in English, by a single arbitrator. COUNSEL: decide between arbitration and the Malaysian courts; APAC + EU customers may prefer arbitration.

## 11. General

11.1 **Assignment.** Neither party may assign these Terms without the other's consent, except to a successor in a merger, acquisition, or sale of substantially all assets.

11.2 **Notices.** To EIAAW: `legal@eiaawsolutions.com`. To the Customer: the billing contact of the Workspace.

11.3 **Entire agreement.** These Terms, the DPA, and the Privacy Policy are the entire agreement between the parties and supersede prior proposals or discussions.

11.4 **Changes.** EIAAW may update these Terms with 30 days' notice to Customers. Continued use after the effective date constitutes acceptance. Customers may terminate if they reject material changes.

---

**Checklist for counsel:**

- [ ] Confirm Malaysian company registration details and authorised signatory
- [ ] Insert liability cap and carve-outs (gross negligence, wilful misconduct, IP indemnity, data-protection fines)
- [ ] Insert IP indemnity (we defend Customer against claims that the Service infringes third-party IP)
- [ ] Decide arbitration clause vs courts; confirm AIAC rules reference
- [ ] Review "Acceptable Use" against PDPA Section 6 and Schedule 1 (lawful processing conditions)
- [ ] Review Section 9.3 retention windows against sector regulators (MOH, BNM RMiT) for regulated-industry customers
- [ ] Map sections 2.2 (prohibited uses) to Malaysia Communications and Multimedia Act where relevant
- [ ] Confirm SST / digital-service-tax position; may need a Malaysia-specific clause
- [ ] Decide whether to include DPIA support obligations for Enterprise tier
