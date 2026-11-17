<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->certificate_number }}</title>
    <style>
        @page { margin: 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #123; text-align: center; border: 6px solid #0f7a4d; padding: 28px; }
        .organisation { font-size: 15px; letter-spacing: 2px; text-transform: uppercase; color: #0f7a4d; }
        h1 { font-size: 34px; margin: 12px 0 4px; letter-spacing: 3px; }
        h2 { font-size: 24px; margin: 4px 0 18px; font-weight: normal; }
        .intro { font-size: 14px; margin-bottom: 8px; }
        .name { font-size: 30px; font-weight: bold; margin: 8px 0; }
        .detail { font-size: 14px; margin: 4px 0; }
        .event { margin-top: 18px; font-size: 14px; }
        .footer { margin-top: 42px; width: 100%; }
        .signature { display: inline-block; width: 40%; vertical-align: top; font-size: 12px; }
        .line { border-top: 1px solid #123; margin-bottom: 6px; padding-top: 6px; }
        .meta { margin-top: 18px; font-size: 10px; color: #555; }
    </style>
</head>
<body>
    <div class="organisation">Nigeria Police Force</div>
    <h1>CERTIFICATE OF PARTICIPATION</h1>
    <h2>{{ $event->name }}</h2>

    <div class="intro">This is to certify that</div>
    <div class="name">{{ trim(($rank ?? '').' '.$fullName) }}</div>
    @if ($organisation)
        <div class="detail">{{ $organisation }}</div>
    @endif
    <div class="event">
        participated in the {{ $event->name }} held at the {{ $event->venue_name }}, {{ $event->venue_address }},
        {{ $event->start_date->format('jS F Y') }} – {{ $event->end_date->format('jS F Y') }}.
    </div>

    <div class="footer">
        @foreach ($signatures as $signature)
            <div class="signature">
                <div class="line">{{ $signature['name'] }}</div>
                <div>{{ $signature['title'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="meta">
        Certificate No. {{ $certificate->certificate_number }} &nbsp;|&nbsp;
        Summit Tag {{ $registration->summit_tag_number }} &nbsp;|&nbsp;
        Issued {{ $certificate->issued_at->format('jS F Y') }}
    </div>
</body>
</html>
