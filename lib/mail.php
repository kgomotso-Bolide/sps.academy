<?php
declare(strict_types=1);

/* Notification email.
 *
 * A NOTIFICATION, not the record. The record is the database row. This is the
 * whole difference from the FormSubmit arrangement it replaces: there, if the
 * mail failed or was filed in the wrong folder, the registration simply did not
 * exist anywhere. Here, mail failing is an inconvenience — the row is already
 * committed and the admin list will still show it.
 *
 * DELIVERABILITY WARNING, and this needs a DNS change to fix properly:
 * centenarynetworks.com receives its mail through Google Workspace, and its SPF
 * record therefore authorises Google's servers to send as that domain. Xneelo's
 * mail server is not in that record. Mail sent from here claiming to be
 * @centenarynetworks.com will fail SPF and is likely to be filed as spam or
 * rejected outright.
 *
 * So: From is the subdomain we control and can add an SPF record for, and the
 * human address goes in Reply-To, where it does not affect authentication.
 * Until an SPF record for sps.centenarynetworks.com exists at GoDaddy naming
 * Xneelo, treat these notifications as unreliable and read the admin list.
 */

defined('APP_BOOTED') or exit('lib/mail.php is not a page.');

function notify(string $subject, string $body, ?string $replyTo = null): bool
{
    $to = (string) (app_config('notify_email') ?? '');
    if ($to === '') {
        app_log('NOTIFY SKIPPED — no notify_email configured');
        return false;
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'sps.centenarynetworks.com';
    $host = preg_replace('/[^a-z0-9.\-]/i', '', $host);
    $from = 'noreply@' . $host;

    $headers = [
        'From: ' . tenant_name() . ' <' . $from . '>',
        'Content-Type: text/plain; charset=UTF-8',
        'MIME-Version: 1.0',
        'X-Mailer: sps-academy',
        // Stops holiday auto-replies and out-of-office loops bouncing back.
        'Auto-Submitted: auto-generated',
    ];
    if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    // -f sets the envelope sender, which is what SPF is actually checked
    // against. Without it, the server's default account is used and the
    // mismatch with the From header makes the failure worse, not better.
    $ok = @mail($to, $subject, $body, implode("\r\n", $headers), '-f' . $from);

    if (!$ok) app_log('NOTIFY FAILED to ' . $to . ' — ' . $subject);
    return $ok;
}

function tenant_name(): string
{
    $t = tenant();
    return (string) ($t['academy_name'] ?? 'Academy');
}
