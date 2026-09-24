@extends('marketing.legal._layout')

@section('robots', 'noindex, follow')
@section('legal-archive-notice')
    <p style="background:var(--bg-warm);border:1px solid var(--line-soft);border-radius:12px;padding:12px 16px;font-size:14px;">Superseded version, kept for reference. It applied from 24 September 2026 until 25 September 2026. <a href="{{ route('marketing.privacy') }}">Read the current version.</a></p>
@endsection

@section('title', 'Privacy Notice — EIAAW Workforce (PDPA, English & Bahasa Malaysia)')
@section('description', 'How EIAAW Workforce collects, uses, shares and protects personal data under Malaysia’s PDPA and other APAC privacy laws. In English and Bahasa Malaysia.')

@section('legal-title', 'Privacy notice')
@section('legal-lede', 'What personal data EIAAW Workforce handles, why, who receives it, and the rights you have.')
@section('legal-updated', '24 September 2026')

@php $dpo = config('eiaaw.privacy_email'); @endphp

@section('legal-body')
    <p class="lg-lang"><a href="#en">English</a> · <a href="#bm">Bahasa Malaysia</a></p>

    <div id="en">
    <p>This notice explains how EIAAW SOLUTIONS handles personal data in connection with EIAAW Workforce: this website (ep.eiaawsolutions.com) and the customer workspaces at <code>*.ep.eiaawsolutions.com</code>. It is issued under Malaysia’s Personal Data Protection Act 2010 (as amended in 2024) and written to also meet the privacy laws of the other Asia-Pacific countries our visitors and customers come from, including Singapore, Indonesia, Thailand, the Philippines, Vietnam, Australia, New Zealand, Japan, South Korea, Hong Kong and India. A Bahasa Malaysia version follows; both versions have the same meaning.</p>

    <h2>1. Who we are, and our two roles</h2>
    <p>EIAAW SOLUTIONS, registered in Malaysia under SSM {{ config('eiaaw.company_reg_no') }}, Kuala Lumpur, provides EIAAW Workforce. We play two different roles:</p>
    <ul>
        <li><strong>We decide how your data is used (data user / controller)</strong> for visitors to this website, people who contact us, and the people who sign up for, administer and pay for a workspace.</li>
        <li><strong>We process data for our customers (data processor)</strong> for the employee, payroll, leave, asset, claims and accounting records that a customer puts into its workspace. There, the employer that owns the workspace decides how the data is used, and we act only on its instructions under our <a href="{{ route('marketing.dpa') }}">Data Processing Agreement</a>. If you are an employee with a question about your records, please ask your employer first; we will help them respond.</li>
    </ul>
    <p>Contact our Data Protection Officer at <a href="mailto:{{ $dpo }}">{{ $dpo }}</a> (subject “DPO”) about anything in this notice.</p>

    <h2>2. What we collect, why, and on what basis</h2>
    <ul>
        <li><strong>Enquiry form (“Talk to us”).</strong> Name, email and message are required so we can reply; phone and company are optional. Purpose: to answer and follow up on your enquiry. Basis: your consent, given by ticking the box on the form.</li>
        <li><strong>Chat assistant on this website.</strong> Before the assistant answers, we ask for your name, email and phone (company is optional). These go to our own CRM, which runs on the EIAAW Sales Agent platform, so our team can follow up. Your messages are sent to our AI model provider to generate replies, and the conversation is kept with your IP address. Basis: your consent, given by ticking the box before you chat.</li>
        <li><strong>Voice agent.</strong> Starting a call connects you to an AI voice agent run on our Sales Agent platform with our voice AI provider. The call is recorded and transcribed so the agent can respond and our team can follow up. The agent always says it is an AI. Basis: your consent, given when you start the call after reading the notice shown before it.</li>
        <li><strong>Signing up for a workspace.</strong> Work email, name, company name, workspace address, chosen plan, a password (stored only as a one-way hash), the IP address and browser used to sign up, and when you agreed to our Terms and this notice. Purpose: to create and secure your workspace and contact you about it. Basis: to enter into and perform our contract with you.</li>
        <li><strong>Billing.</strong> Payments are handled by Stripe. We keep the Stripe customer and subscription identifiers, your plan, seat count and invoices; we never see or store full card numbers. Basis: our contract, and our legal obligation to keep financial records.</li>
        <li><strong>Using a workspace.</strong> Sign-in events, two-factor authentication status, an audit log of actions taken, and questions put to the Workforce Assistant with its answers. Purpose: to run the service, keep it secure, support you and apply each workspace’s AI usage limit. Basis: our contract, and our legitimate interest in security.</li>
        <li><strong>Ad measurement.</strong> Only if you allow it, and only on these marketing pages: the Meta Pixel measures our Facebook and Instagram ads. It receives technical data such as pages viewed, device and browser, never the contents of forms, chats or workspaces. It never runs inside the signed-in app. Basis: your consent through the cookie choices.</li>
        <li><strong>Security logs.</strong> Our hosting and network providers log technical data such as IP addresses to keep the service secure and working. Basis: our legitimate interest in security, and legal obligations.</li>
    </ul>
    <p>If you don’t give us the required details, we can’t reply to your enquiry, start a chat or create a workspace; everything else on the site still works. Please don’t put sensitive personal data (such as health or identity-document details) into enquiry forms or the chat.</p>

    <h2>3. AI features</h2>
    <p>The chat assistant on this website and the Workforce Assistant inside workspaces use Anthropic’s Claude models. The Workforce Assistant answers only from records the signed-in person is already allowed to see, shows which records it used, and cannot change anything in the workspace. Anthropic does not use data sent through its commercial API to train its models, and we do not use workspace data to train any AI model. We don’t make decisions with legal or similarly significant effects about anyone by automated means alone: the software calculates payroll deductions from the statutory rates in the workspace, and people at the employer review and approve each pay run.</p>

    <h2 id="cookies">4. Cookies and similar technologies</h2>
    <p>Nothing that tracks you loads until you choose. You can change your choice at any time with <a href="#cookies" data-cookie-settings>Cookie settings</a>, also in the footer of every page.</p>
    <div class="lg-table">
        <table>
            <thead><tr><th>Name</th><th>Set by</th><th>Purpose</th><th>Duration</th></tr></thead>
            <tbody>
                <tr><td><code>eiaaw_workforce_session</code></td><td>EIAAW</td><td>Keeps you signed in and protects the session (strictly necessary)</td><td>2 hours of inactivity</td></tr>
                <tr><td><code>XSRF-TOKEN</code></td><td>EIAAW</td><td>Protects forms against cross-site request forgery (strictly necessary)</td><td>2 hours</td></tr>
                <tr><td><code>eiaaw_consent</code></td><td>EIAAW (cookie shared across eiaawsolutions.com sites)</td><td>Remembers your cookie choice so other EIAAW sites don’t ask again</td><td>6 months</td></tr>
                <tr><td><code>eiaawConsent</code></td><td>EIAAW (browser storage)</td><td>Remembers your cookie choice</td><td>Until you clear it</td></tr>
                <tr><td><code>epChatGate</code></td><td>EIAAW (browser storage)</td><td>Remembers you’ve introduced yourself in the chat</td><td>This browser session</td></tr>
                <tr><td><code>_fbp</code></td><td>Meta, only with advertising consent</td><td>Measures our Facebook and Instagram ads</td><td>Up to 3 months</td></tr>
            </tbody>
        </table>
    </div>
    <p>These pages also load fonts from Google Fonts, which receives your IP address to deliver them.</p>

    <h2>5. Who we share it with</h2>
    <p>We don’t sell personal data or share it with anyone for their own marketing. We share it only with our own CRM (on the EIAAW Sales Agent platform), with authorities, courts or regulators when the law requires it, and with these service providers, who work for us under contract:</p>
    <div class="lg-table">
        <table>
            <thead><tr><th>Provider</th><th>What it does for us</th><th>Where it processes data</th></tr></thead>
            <tbody>
                <tr><td>Railway</td><td>Hosts the application and its database</td><td>Singapore</td></tr>
                <tr><td>Cloudflare</td><td>DNS, content delivery and security filtering</td><td>Global network</td></tr>
                <tr><td>Anthropic</td><td>AI models for the chat assistant and the Workforce Assistant</td><td>United States</td></tr>
                <tr><td>Stripe</td><td>Payments and subscription billing</td><td>United States and other countries</td></tr>
                <tr><td>Resend</td><td>Sends account and notification emails</td><td>United States</td></tr>
                <tr><td>Retell AI</td><td>Runs voice agent calls</td><td>United States</td></tr>
                <tr><td>Google</td><td>Web fonts on the marketing pages</td><td>Global network</td></tr>
                <tr><td>Meta</td><td>Ad measurement, only with your consent</td><td>United States</td></tr>
            </tbody>
        </table>
    </div>
    <p>If we add or replace a provider that processes workspace data, we update this list and give customers notice as set out in the Data Processing Agreement.</p>

    <h2>6. Transfers outside your country</h2>
    <p>Workspace data is stored in Singapore. Some providers above process data in other countries, including the United States. We use providers bound by data-protection terms that protect your data to a standard comparable to Malaysian law, and where your local law requires consent for such transfers, we ask for it on the form.</p>

    <h2>7. How long we keep it</h2>
    <ul>
        <li>Enquiry and chat details: up to 24 months after our last contact with you, then deleted, unless you become a customer.</li>
        <li>Unfinished signups (the email was never confirmed): deleted after 90 days.</li>
        <li>Workspace data: for as long as the subscription runs. After cancellation the workspace is read-only for 30 days so you can export, then deleted from the primary database; any remaining copies are removed within 90 days of cancellation.</li>
        <li>Billing records: 7 years, as Malaysian tax law requires.</li>
        <li>Security logs: only as long as needed to protect the service.</li>
    </ul>

    <h2>8. Security</h2>
    <p>The service runs over HTTPS only. Each workspace’s data is kept apart by row-level security in the database, passwords are stored as one-way hashes, two-factor authentication is available to every user, and access to customer data inside EIAAW is limited to the people who need it to run and support the service. If a data breach is likely to cause significant harm, we will notify the Personal Data Protection Commissioner of Malaysia within 72 hours and the affected people without undue delay, and other regulators as their law requires. For workspace data we notify the customer without undue delay so it can meet its own obligations.</p>

    <h2>9. Your rights</h2>
    <ul>
        <li>See the personal data we hold about you and get a copy, including in a portable, machine-readable format.</li>
        <li>Correct it, or ask us to delete it.</li>
        <li>Withdraw your consent at any time, object to or ask us to limit processing, and stop us contacting you. Withdrawing doesn’t affect what we did before.</li>
        <li>Complain to your data protection regulator (see below).</li>
    </ul>
    <p>Email <a href="mailto:{{ $dpo }}">{{ $dpo }}</a> with “Personal data request” in the subject. We’ll verify it’s you, then respond within 21 days, or sooner where your local law requires. There is no charge unless the law allows one for repeated requests, and we’ll tell you first. For records held in an employer’s workspace, we pass your request to the employer and help it respond. We don’t send marketing unless you ask for it, and every marketing message lets you opt out.</p>

    <h2>10. Regulators</h2>
    <p>You can contact us first, and you can also complain to your regulator: Malaysia — Personal Data Protection Department (JPDP); Singapore — Personal Data Protection Commission (PDPC); Indonesia — Ministry of Communication and Digital Affairs (Komdigi); Thailand — Personal Data Protection Committee (PDPC); Philippines — National Privacy Commission (NPC); Vietnam — Ministry of Public Security (A05); Australia — Office of the Australian Information Commissioner (OAIC); New Zealand — Office of the Privacy Commissioner; Japan — Personal Information Protection Commission (PPC); South Korea — Personal Information Protection Commission (PIPC); Hong Kong — Office of the Privacy Commissioner for Personal Data (PCPD); India — Data Protection Board of India.</p>

    <h2>11. Children</h2>
    <p>EIAAW Workforce is a business service and isn’t directed at anyone under 18. Employers may hold records of employees or interns under 18 in their workspace; they are responsible for having a lawful basis, and we process those records only on their instructions.</p>

    <h2>12. Changes</h2>
    <p>If we change how we handle personal data, we’ll update this page and the date at the top. If a change affects what you consented to, we’ll ask again.</p>
    </div>

    <section id="bm" lang="ms" class="lg-bm">
    <h2>Notis Privasi (Bahasa Malaysia)</h2>
    <p>Notis ini menerangkan cara EIAAW SOLUTIONS mengendalikan data peribadi berkaitan EIAAW Workforce: laman web ini (ep.eiaawsolutions.com) dan ruang kerja pelanggan di <code>*.ep.eiaawsolutions.com</code>. Notis ini dikeluarkan di bawah Akta Perlindungan Data Peribadi 2010 (seperti yang dipinda pada 2024) dan juga ditulis untuk memenuhi undang-undang privasi negara Asia Pasifik lain yang pelawat dan pelanggan kami datang, termasuk Singapura, Indonesia, Thailand, Filipina, Vietnam, Australia, New Zealand, Jepun, Korea Selatan, Hong Kong dan India. Versi Bahasa Inggeris dan versi Bahasa Malaysia membawa maksud yang sama.</p>

    <h3>1. Siapa kami, dan dua peranan kami</h3>
    <p>EIAAW SOLUTIONS, berdaftar di Malaysia di bawah SSM {{ config('eiaaw.company_reg_no') }}, Kuala Lumpur, menyediakan EIAAW Workforce. Kami memainkan dua peranan:</p>
    <ul>
        <li><strong>Kami menentukan cara data anda digunakan (pengguna data)</strong> bagi pelawat laman web ini, orang yang menghubungi kami, dan orang yang mendaftar, mentadbir dan membayar bagi sesuatu ruang kerja.</li>
        <li><strong>Kami memproses data bagi pihak pelanggan (pemproses data)</strong> bagi rekod pekerja, gaji, cuti, aset, tuntutan dan perakaunan yang dimasukkan oleh pelanggan ke dalam ruang kerjanya. Dalam hal ini, majikan yang memiliki ruang kerja menentukan cara data digunakan, dan kami hanya bertindak atas arahannya di bawah <a href="{{ route('marketing.dpa') }}">Perjanjian Pemprosesan Data</a> kami. Jika anda seorang pekerja yang mempunyai pertanyaan tentang rekod anda, sila tanya majikan anda terlebih dahulu; kami akan membantu mereka menjawab.</li>
    </ul>
    <p>Hubungi Pegawai Perlindungan Data kami di <a href="mailto:{{ $dpo }}">{{ $dpo }}</a> (subjek “DPO”) mengenai apa-apa perkara dalam notis ini.</p>

    <h3>2. Data yang kami kumpul, tujuan dan asasnya</h3>
    <ul>
        <li><strong>Borang pertanyaan (“Talk to us”).</strong> Nama, e-mel dan mesej diwajibkan supaya kami dapat membalas; telefon dan syarikat adalah pilihan. Tujuan: menjawab dan membuat susulan pertanyaan anda. Asas: persetujuan anda, yang diberi dengan menanda kotak pada borang.</li>
        <li><strong>Pembantu sembang di laman web ini.</strong> Sebelum pembantu menjawab, kami meminta nama, e-mel dan telefon anda (syarikat adalah pilihan). Butiran ini disimpan dalam CRM kami sendiri, yang berjalan pada platform EIAAW Sales Agent, supaya pasukan kami boleh membuat susulan. Mesej anda dihantar kepada penyedia model AI kami untuk menjana jawapan, dan perbualan disimpan bersama alamat IP anda. Asas: persetujuan anda, yang diberi dengan menanda kotak sebelum bersembang.</li>
        <li><strong>Ejen suara.</strong> Memulakan panggilan menghubungkan anda dengan ejen suara AI yang dijalankan pada platform Sales Agent kami bersama penyedia AI suara kami. Panggilan dirakam dan ditranskripsi supaya ejen dapat menjawab dan pasukan kami dapat membuat susulan. Ejen sentiasa memaklumkan bahawa ia adalah AI. Asas: persetujuan anda, yang diberi apabila anda memulakan panggilan selepas membaca notis yang dipaparkan sebelumnya.</li>
        <li><strong>Mendaftar ruang kerja.</strong> E-mel kerja, nama, nama syarikat, alamat ruang kerja, pelan yang dipilih, kata laluan (disimpan hanya sebagai cincangan sehala), alamat IP dan pelayar yang digunakan semasa mendaftar, dan masa anda bersetuju dengan Terma dan notis ini. Tujuan: mewujudkan dan melindungi ruang kerja anda serta menghubungi anda mengenainya. Asas: untuk memeterai dan melaksanakan kontrak kami dengan anda.</li>
        <li><strong>Pengebilan.</strong> Pembayaran dikendalikan oleh Stripe. Kami menyimpan pengecam pelanggan dan langganan Stripe, pelan, bilangan tempat dan invois anda; kami tidak sekali-kali melihat atau menyimpan nombor kad penuh. Asas: kontrak kami, dan kewajipan undang-undang untuk menyimpan rekod kewangan.</li>
        <li><strong>Menggunakan ruang kerja.</strong> Peristiwa log masuk, status pengesahan dua faktor, log audit tindakan yang diambil, serta soalan kepada Workforce Assistant dan jawapannya. Tujuan: menjalankan perkhidmatan, memastikannya selamat, memberi sokongan dan menguatkuasakan had penggunaan AI setiap ruang kerja. Asas: kontrak kami, dan kepentingan sah kami terhadap keselamatan.</li>
        <li><strong>Pengukuran iklan.</strong> Hanya jika anda membenarkannya, dan hanya di halaman pemasaran ini: Meta Pixel mengukur iklan Facebook dan Instagram kami. Ia menerima data teknikal seperti halaman yang dilihat, peranti dan pelayar, tetapi tidak sekali-kali kandungan borang, sembang atau ruang kerja. Ia tidak pernah berjalan di dalam aplikasi selepas log masuk. Asas: persetujuan anda melalui pilihan kuki.</li>
        <li><strong>Log keselamatan.</strong> Penyedia pengehosan dan rangkaian kami merekod data teknikal seperti alamat IP untuk memastikan perkhidmatan selamat dan berfungsi. Asas: kepentingan sah kami terhadap keselamatan, dan kewajipan undang-undang.</li>
    </ul>
    <p>Jika anda tidak memberikan butiran yang diwajibkan, kami tidak dapat membalas pertanyaan anda, memulakan sembang atau mewujudkan ruang kerja; bahagian lain laman web tetap berfungsi. Sila jangan masukkan data peribadi sensitif (seperti maklumat kesihatan atau dokumen pengenalan) dalam borang pertanyaan atau sembang.</p>

    <h3>3. Ciri AI</h3>
    <p>Pembantu sembang di laman web ini dan Workforce Assistant di dalam ruang kerja menggunakan model Claude daripada Anthropic. Workforce Assistant hanya menjawab berdasarkan rekod yang orang yang log masuk sudah dibenarkan melihat, menunjukkan rekod yang digunakannya, dan tidak boleh mengubah apa-apa dalam ruang kerja. Anthropic tidak menggunakan data yang dihantar melalui API komersialnya untuk melatih modelnya, dan kami tidak menggunakan data ruang kerja untuk melatih mana-mana model AI. Kami tidak membuat keputusan yang mempunyai kesan undang-undang atau kesan ketara yang serupa terhadap sesiapa secara automatik semata-mata: perisian mengira potongan gaji berdasarkan kadar berkanun dalam ruang kerja, dan pihak majikan menyemak serta meluluskan setiap larian gaji.</p>

    <h3>4. Kuki dan teknologi serupa</h3>
    <p>Tiada apa-apa yang menjejaki anda dimuatkan sehingga anda membuat pilihan. Anda boleh menukar pilihan pada bila-bila masa melalui <a href="#cookies" data-cookie-settings>Cookie settings</a>, juga di bahagian bawah setiap halaman. Kuki yang digunakan: <code>eiaaw_workforce_session</code> (mengekalkan log masuk dan melindungi sesi, 2 jam tanpa aktiviti), <code>XSRF-TOKEN</code> (melindungi borang, 2 jam), <code>eiaaw_consent</code> (mengingati pilihan kuki anda merentas laman eiaawsolutions.com, 6 bulan), <code>eiaawConsent</code> (mengingati pilihan kuki anda, sehingga anda memadamnya), <code>epChatGate</code> (mengingati bahawa anda telah memperkenalkan diri dalam sembang, untuk sesi pelayar ini) dan <code>_fbp</code> (Meta, hanya dengan persetujuan pengiklanan, sehingga 3 bulan). Halaman ini juga memuatkan fon daripada Google Fonts, yang menerima alamat IP anda untuk menghantarnya.</p>

    <h3>5. Pihak yang menerima data anda</h3>
    <p>Kami tidak menjual data peribadi atau berkongsinya dengan sesiapa untuk pemasaran mereka sendiri. Kami berkongsi hanya dengan CRM kami sendiri (pada platform EIAAW Sales Agent), dengan pihak berkuasa, mahkamah atau pengawal selia apabila dikehendaki oleh undang-undang, dan dengan penyedia perkhidmatan yang bekerja untuk kami di bawah kontrak: Railway (mengehos aplikasi dan pangkalan data, Singapura), Cloudflare (DNS, penghantaran kandungan dan penapisan keselamatan, rangkaian global), Anthropic (model AI bagi pembantu sembang dan Workforce Assistant, Amerika Syarikat), Stripe (pembayaran dan pengebilan langganan, Amerika Syarikat dan negara lain), Resend (menghantar e-mel akaun dan pemberitahuan, Amerika Syarikat), Retell AI (menjalankan panggilan ejen suara, Amerika Syarikat), Google (fon web di halaman pemasaran, rangkaian global) dan Meta (pengukuran iklan, hanya dengan persetujuan anda, Amerika Syarikat). Jika kami menambah atau menggantikan penyedia yang memproses data ruang kerja, kami mengemas kini senarai ini dan memberi notis kepada pelanggan seperti yang ditetapkan dalam Perjanjian Pemprosesan Data.</p>

    <h3>6. Pemindahan ke luar negara anda</h3>
    <p>Data ruang kerja disimpan di Singapura. Sesetengah penyedia di atas memproses data di negara lain, termasuk Amerika Syarikat. Kami menggunakan penyedia yang terikat dengan terma perlindungan data yang melindungi data anda pada tahap setanding dengan undang-undang Malaysia, dan jika undang-undang tempatan anda memerlukan persetujuan bagi pemindahan sedemikian, kami memintanya pada borang.</p>

    <h3>7. Tempoh penyimpanan</h3>
    <ul>
        <li>Butiran pertanyaan dan sembang: sehingga 24 bulan selepas hubungan terakhir kami dengan anda, kemudian dipadam, kecuali anda menjadi pelanggan.</li>
        <li>Pendaftaran yang tidak selesai (e-mel tidak pernah disahkan): dipadam selepas 90 hari.</li>
        <li>Data ruang kerja: selagi langganan berjalan. Selepas pembatalan, ruang kerja menjadi baca-sahaja selama 30 hari supaya anda boleh mengeksport data, kemudian dipadam daripada pangkalan data utama; sebarang salinan yang tinggal dibuang dalam tempoh 90 hari selepas pembatalan.</li>
        <li>Rekod pengebilan: 7 tahun, seperti yang dikehendaki oleh undang-undang cukai Malaysia.</li>
        <li>Log keselamatan: hanya selama yang diperlukan untuk melindungi perkhidmatan.</li>
    </ul>

    <h3>8. Keselamatan</h3>
    <p>Perkhidmatan ini berjalan melalui HTTPS sahaja. Data setiap ruang kerja diasingkan melalui keselamatan peringkat baris dalam pangkalan data, kata laluan disimpan sebagai cincangan sehala, pengesahan dua faktor tersedia untuk setiap pengguna, dan akses kepada data pelanggan di dalam EIAAW terhad kepada mereka yang memerlukannya untuk menjalankan dan menyokong perkhidmatan. Jika berlaku pelanggaran data yang mungkin menyebabkan kemudaratan ketara, kami akan memaklumkan Pesuruhjaya Perlindungan Data Peribadi Malaysia dalam tempoh 72 jam dan individu yang terjejas tanpa kelewatan yang tidak wajar, serta pengawal selia lain seperti yang dikehendaki oleh undang-undang mereka. Bagi data ruang kerja, kami memaklumkan pelanggan tanpa kelewatan yang tidak wajar supaya mereka dapat memenuhi kewajipan mereka sendiri.</p>

    <h3>9. Hak anda</h3>
    <ul>
        <li>Melihat data peribadi yang kami simpan tentang anda dan mendapatkan salinannya, termasuk dalam format mudah alih yang boleh dibaca mesin.</li>
        <li>Membetulkannya, atau meminta kami memadamnya.</li>
        <li>Menarik balik persetujuan anda pada bila-bila masa, membantah atau meminta kami mengehadkan pemprosesan, dan menghentikan kami daripada menghubungi anda. Penarikan balik tidak menjejaskan pemprosesan sebelumnya.</li>
        <li>Membuat aduan kepada pengawal selia perlindungan data anda (lihat di bawah).</li>
    </ul>
    <p>E-mel <a href="mailto:{{ $dpo }}">{{ $dpo }}</a> dengan “Personal data request” sebagai subjek. Kami akan mengesahkan identiti anda, kemudian membalas dalam tempoh 21 hari, atau lebih awal jika undang-undang tempatan anda memerlukannya. Tiada caj dikenakan kecuali undang-undang membenarkannya bagi permintaan berulang, dan kami akan memaklumkan anda terlebih dahulu. Bagi rekod dalam ruang kerja majikan, kami menyalurkan permintaan anda kepada majikan dan membantu mereka menjawab. Kami tidak menghantar bahan pemasaran kecuali anda memintanya, dan setiap mesej pemasaran membolehkan anda menarik diri.</p>

    <h3>10. Pengawal selia</h3>
    <p>Anda boleh menghubungi kami terlebih dahulu, dan anda juga boleh membuat aduan kepada pengawal selia anda. Di Malaysia: Jabatan Perlindungan Data Peribadi (JPDP). Senarai pengawal selia bagi negara lain terdapat dalam seksyen 10 versi Bahasa Inggeris di atas.</p>

    <h3>11. Kanak-kanak</h3>
    <p>EIAAW Workforce ialah perkhidmatan perniagaan dan tidak ditujukan kepada sesiapa di bawah umur 18 tahun. Majikan mungkin menyimpan rekod pekerja atau pelatih di bawah umur 18 tahun dalam ruang kerja mereka; majikan bertanggungjawab memastikan asas yang sah, dan kami memproses rekod tersebut hanya atas arahan mereka.</p>

    <h3>12. Perubahan</h3>
    <p>Jika kami mengubah cara kami mengendalikan data peribadi, kami akan mengemas kini halaman ini dan tarikh di bahagian atas. Jika perubahan itu menjejaskan perkara yang anda telah bersetuju, kami akan meminta persetujuan anda semula.</p>
    </section>
@endsection
