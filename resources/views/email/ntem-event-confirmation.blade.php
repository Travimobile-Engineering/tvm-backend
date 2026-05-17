<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional //EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>NTEM Abuja Event Registration Confirmed</title>

    <style type="text/css">
        @media only screen and (min-width: 560px) {
            .email-wrapper { width: 540px !important; }
        }
        @media only screen and (max-width: 560px) {
            .email-wrapper { width: 100% !important; }
            .email-card   { padding: 28px 16px !important; }
            .ref-badge    { font-size: 17px !important; padding: 12px 16px !important; }
            .details-table td { display: block !important; width: 100% !important; padding: 4px 0 !important; }
            .details-label { padding-bottom: 0 !important; }
        }

        body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            background-color: #F0F4F8;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1A2233;
        }

        table, td { border-collapse: collapse; vertical-align: top; }
        p { margin: 0; }
        a { text-decoration: none; }

        /* ── Brand colours ── */
        .bg-brand  { background-color: #0A2A5E; }   /* deep navy                */
        .bg-accent { background-color: #08d786; }   /* Travi / NTEM green accent */
        .clr-brand { color: #0A2A5E; }
        .clr-accent{ color: #08d786; }
    </style>
</head>

<body style="margin:0;padding:0;background-color:#F0F4F8;">

<table width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background-color:#F0F4F8;padding:32px 0;">
    <tbody>
    <tr>
        <td align="center">

            <!-- ═══════════════════════════════════════════════════
                 CARD WRAPPER
            ═══════════════════════════════════════════════════ -->
            <table class="email-wrapper" width="540" cellpadding="0" cellspacing="0" border="0"
                   style="border-radius:12px;overflow:hidden;
                          box-shadow:0 4px 24px rgba(10,42,94,0.13);">

                <!-- ─── HEADER BANNER ─────────────────────────── -->
                <tr>
                    <td class="bg-brand" align="center"
                        style="background-color:#0A2A5E;padding:32px 24px 24px;">

                        <!--
                            Cloudinary transformations applied to the NTEM logo:
                              q_auto:best  – lossless-quality auto selection
                              f_png        – force PNG so email clients render it correctly
                              w_180        – cap width at 180 px (retina-safe in most inboxes)
                              e_sharpen:70 – crisp, detailed edges
                              e_brightness:8  – subtle lift for a bright/glowing impression
                              e_vibrance:25   – pop the brand colours without oversaturation
                        -->
                        <img
                            src="https://res.cloudinary.com/ducbzbm1d/image/upload/q_auto:best,f_png,w_180,e_sharpen:70,e_brightness:8,e_vibrance:25/v1779027769/ntem-logo_uu4r4y.png"
                            alt="NTEM Logo"
                            width="180"
                            style="display:block;border:none;outline:none;
                                   max-width:180px;height:auto;margin:0 auto;">

                        <p style="margin-top:20px;font-size:13px;color:#A8C0E0;
                                  letter-spacing:2px;text-transform:uppercase;
                                  font-weight:600;">
                            Hotel Onboarding & Check-In App Exhibition &amp; Meet
                        </p>
                    </td>
                </tr>

                <!-- ─── GREEN ACCENT BAR ──────────────────────── -->
                <tr>
                    <td class="bg-accent"
                        style="background-color:#08d786;height:5px;font-size:0;line-height:0;">
                        &nbsp;
                    </td>
                </tr>

                <!-- ─── EMAIL BODY ────────────────────────────── -->
                <tr>
                    <td class="email-card"
                        style="background-color:#ffffff;padding:40px 36px;">

                        <!-- Greeting -->
                        <p style="font-size:22px;font-weight:700;color:#0A2A5E;
                                  margin-bottom:8px;">
                            Hello, {{ $fullName }}!
                        </p>
                        <p style="font-size:15px;color:#4A5568;line-height:1.7;
                                  margin-bottom:28px;">
                            Your registration for the <strong>NTEM Abuja Event</strong> has been
                            received and confirmed. We look forward to welcoming you.
                        </p>

                        <!-- Reference ID badge -->
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="margin-bottom:28px;">
                            <tr>
                                <td align="center">
                                    <div class="ref-badge"
                                         style="display:inline-block;
                                                background:linear-gradient(135deg,#0A2A5E 0%,#163f8a 100%);
                                                color:#ffffff;
                                                border-radius:8px;
                                                padding:16px 28px;
                                                text-align:center;">
                                        <p style="font-size:11px;color:#A8C0E0;
                                                  letter-spacing:2px;text-transform:uppercase;
                                                  margin-bottom:6px;">
                                            Your Reference ID
                                        </p>
                                        <p style="font-size:22px;font-weight:800;
                                                  letter-spacing:1px;color:#08d786;">
                                            {{ $referenceId }}
                                        </p>
                                        <p style="font-size:11px;color:#A8C0E0;margin-top:6px;">
                                            Please quote this ID in all correspondence
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <!-- ── Registration summary ── -->
                        <p style="font-size:13px;font-weight:700;color:#0A2A5E;
                                  text-transform:uppercase;letter-spacing:1.5px;
                                  margin-bottom:12px;">
                            Registration Summary
                        </p>

                        <table class="details-table" width="100%" cellpadding="0" cellspacing="0"
                               border="0"
                               style="background-color:#F7FAFC;border-radius:8px;
                                      border:1px solid #E2E8F0;margin-bottom:28px;">
                            <tbody>

                            <!-- Row helper: label | value -->
                            <tr style="border-bottom:1px solid #E2E8F0;">
                                <td class="details-label"
                                    style="padding:12px 16px;font-size:13px;font-weight:600;
                                           color:#718096;width:40%;white-space:nowrap;">
                                    Full Name
                                </td>
                                <td style="padding:12px 16px;font-size:14px;color:#1A2233;
                                           font-weight:500;">
                                    {{ $fullName }}
                                </td>
                            </tr>

                            <tr style="border-bottom:1px solid #E2E8F0;">
                                <td class="details-label"
                                    style="padding:12px 16px;font-size:13px;font-weight:600;
                                           color:#718096;width:40%;white-space:nowrap;">
                                    Email Address
                                </td>
                                <td style="padding:12px 16px;font-size:14px;color:#1A2233;
                                           font-weight:500;">
                                    {{ $email }}
                                </td>
                            </tr>

                            <tr style="border-bottom:1px solid #E2E8F0;">
                                <td class="details-label"
                                    style="padding:12px 16px;font-size:13px;font-weight:600;
                                           color:#718096;white-space:nowrap;">
                                    Organization
                                </td>
                                <td style="padding:12px 16px;font-size:14px;color:#1A2233;
                                           font-weight:500;">
                                    {{ $organization }}
                                </td>
                            </tr>

                            <tr style="border-bottom:1px solid #E2E8F0;">
                                <td class="details-label"
                                    style="padding:12px 16px;font-size:13px;font-weight:600;
                                           color:#718096;white-space:nowrap;">
                                    Job Title
                                </td>
                                <td style="padding:12px 16px;font-size:14px;color:#1A2233;
                                           font-weight:500;">
                                    {{ $jobTitle }}
                                </td>
                            </tr>

                            <tr>
                                <td class="details-label"
                                    style="padding:12px 16px;font-size:13px;font-weight:600;
                                           color:#718096;white-space:nowrap;">
                                    State
                                </td>
                                <td style="padding:12px 16px;font-size:14px;color:#1A2233;
                                           font-weight:500;">
                                    {{ $state }}
                                </td>
                            </tr>

                            </tbody>
                        </table>

                        <!-- Next-steps note -->
                        <table width="100%" cellpadding="0" cellspacing="0" border="0"
                               style="margin-bottom:28px;">
                            <tr>
                                <td style="background-color:#EBF8FF;border-left:4px solid #08d786;
                                           border-radius:0 6px 6px 0;padding:14px 16px;">
                                    <p style="font-size:13px;color:#2D3748;line-height:1.7;">
                                        Further details — including onboardings, schedule, and
                                        full trainings — will be sent to your registered
                                        email address.
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <!-- Sign-off -->
                        <p style="font-size:14px;color:#4A5568;line-height:1.8;">
                            Warm regards,<br>
                            <strong style="color:#0A2A5E;">The NTEM Team</strong>
                        </p>

                    </td>
                </tr>

                <!-- ─── FOOTER ─────────────────────────────────── -->
                <tr>
                    <td class="bg-brand"
                        style="background-color:#0A2A5E;padding:20px 24px;text-align:center;">

                        <!-- Social icons row -->
                        <table align="center" cellpadding="0" cellspacing="0" border="0"
                               style="margin-bottom:12px;">
                            <tr>
                                <td style="padding:0 6px;">
                                    <a href="https://web.facebook.com/profile.php?id=100083512921307"
                                       title="Facebook" target="_blank">
                                        <img src="https://cdn.tools.unlayer.com/social/icons/circle/facebook.png"
                                             alt="Facebook" width="26"
                                             style="display:block;border:none;max-width:26px;">
                                    </a>
                                </td>
                                <td style="padding:0 6px;">
                                    <a href="https://ng.linkedin.com/in/travi-mobile-8a846523b"
                                       title="LinkedIn" target="_blank">
                                        <img src="https://cdn.tools.unlayer.com/social/icons/circle/linkedin.png"
                                             alt="LinkedIn" width="26"
                                             style="display:block;border:none;max-width:26px;">
                                    </a>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:12px;color:#A8C0E0;margin-bottom:4px;">
                            Need help? Contact us at
                            <a href="mailto:support@travimobile.com"
                               style="color:#08d786;text-decoration:none;">
                                support@travimobile.com
                            </a>
                        </p>
                        <p style="font-size:11px;color:#5A7A9E;margin-top:8px;">
                            &copy; {{ date('Y') }} Travi Mobile. All rights reserved.
                        </p>

                    </td>
                </tr>

            </table>
            <!-- END CARD WRAPPER -->

        </td>
    </tr>
    </tbody>
</table>

</body>
</html>
