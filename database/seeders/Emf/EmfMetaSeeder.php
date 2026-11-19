<?php

namespace Database\Seeders\Emf;

use App\Models\Emf\AuditEvent;
use App\Models\Emf\ExportJob;
use App\Models\Emf\Notification;
use App\Models\Emf\NotificationPreference;
use App\Models\Emf\ReportSchedule;
use Illuminate\Database\Seeder;

class EmfMetaSeeder extends Seeder
{
    public function run(): void
    {
        $notifications = [
            ['not_001', 'critical', 'Critical watchlist hit at Lekki', 'Adaemu Y. Bello matched at 98%. Respond within SLA.', true, 'alert'],
            ['not_002', 'approval', 'Case CS-26-0003 needs closure approval', 'Emeka Okafor submitted the case for supervisor review.', true, 'case'],
            ['not_003', 'expiry', 'Grant GR-26-0006 expires in 6 hours', 'Aisha Bello has a watchlist read grant expiring today.', false, 'grant'],
            ['not_004', 'alert', 'Open incident INC-26-00008 unassigned', 'An unassigned high severity incident is waiting for a responder.', true, 'incident'],
            ['not_005', 'info', 'Weekly analytics report is ready', 'The weekly command performance report has been generated.', false, 'analytics'],
            ['not_006', 'approval', 'Device approval requested', 'Grace Nwosu requested access from a new Samsung device.', true, 'device'],
            ['not_007', 'critical', 'Unverified passenger at Mile 2', 'A passenger without a NIN was flagged during manifest screening.', true, 'alert'],
            ['not_008', 'info', 'Roster published', 'The current week roster has been published.', false, 'roster'],
            ['not_009', 'alert', 'Plate mismatch at Apapa Jetty', 'Vehicle LSD-482-XY matched a watchlist entry.', false, 'alert'],
            ['not_010', 'info', 'Case CS-26-0002 sent back', 'The supervisor returned the case with a clarification request.', false, 'case'],
        ];

        foreach ($notifications as $index => [$id, $type, $title, $body, $needsAction, $link]) {
            Notification::query()->updateOrCreate(['id' => $id], [
                'officer_id' => 'off_ada',
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'needs_action' => $needsAction,
                'status' => $index > 6 ? 'read' : 'unread',
                'link' => $link,
                'read_at' => $index > 6 ? now()->subHours(2) : null,
            ]);
        }

        NotificationPreference::query()->updateOrCreate(['officer_id' => 'off_ada'], [
            'preferences' => [
                'criticalAlert' => ['inApp' => true, 'sms' => true, 'email' => true, 'push' => true],
                'flaggedManifest' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => true],
                'incidentUpdate' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => true],
                'caseApproval' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => false],
                'grantExpiry' => ['inApp' => true, 'sms' => false, 'email' => true, 'push' => false],
                'deviceApproval' => ['inApp' => true, 'sms' => false, 'email' => false, 'push' => false],
                'weeklyDigest' => ['inApp' => false, 'sms' => false, 'email' => true, 'push' => false],
            ],
        ]);

        if (AuditEvent::query()->count() === 0) {
            $events = [
                ['officer.signed_in', 'officer', 'Signed in from the operations room', 'done', 'off_ada'],
                ['manifest.checked', 'manifest', 'Checked manifest MF-26-00001', 'done', 'off_grace'],
                ['alert.escalated', 'incident', 'Escalated AL-26-00001 to INC-26-00001', 'done', 'off_ada'],
                ['watchlist.updated', 'watchlist', 'Updated watchlist entry WL-26-0001', 'warning', 'off_emeka'],
                ['case.closure_approved', 'case', 'Approved closure for CS-26-0004', 'done', 'off_ada'],
                ['grant.revoked', 'access_grant', 'Revoked GR-26-0008 after suspension', 'warning', 'off_ada'],
                ['officer.login_failed', 'officer', 'Failed sign-in for NPF-22510', 'blocked', null],
                ['device.approved', 'device', 'Approved iPad Pro for Adaemu Y. Bello', 'done', 'off_ada'],
                ['analytics.exported', 'export', 'Exported weekly manifest analytics', 'done', 'off_fatima'],
                ['roster.published', 'duty_roster', 'Published roster for the current week', 'done', 'off_ada'],
                ['case.shared', 'case', 'Shared CS-26-0003 with DSS', 'warning', 'off_ada'],
                ['incident.resolved', 'incident', 'Resolved INC-26-00005', 'done', 'off_grace'],
            ];
            foreach ($events as $index => [$action, $target, $detail, $result, $actor]) {
                AuditEvent::create([
                    'actor_id' => $actor,
                    'actor_name' => $actor === null ? 'System' : 'Adaemu Y. Bello',
                    'action' => $action,
                    'target_type' => $target,
                    'target_id' => null,
                    'detail' => $detail,
                    'result' => $result,
                    'ip' => '102.89.34.'.(10 + $index),
                    'metadata' => ['seeded' => true],
                    'occurred_at' => now()->subMinutes(($index + 1) * 23),
                ]);
            }
        }

        ExportJob::query()->updateOrCreate(['id' => 'exp_demo01'], [
            'requested_by' => 'off_ada',
            'report_type' => 'manifests',
            'filters' => ['period' => '7d'],
            'format' => 'csv',
            'status' => 'ready',
            'file_url' => 'https://res.cloudinary.com/demo/raw/upload/v1/emf/exports/demo-manifests.csv',
            'row_count' => 30,
            'watermark' => true,
            'requires_2fa' => true,
            'verified_at' => now()->subMinutes(20),
            'expires_at' => now()->addMinutes(15),
        ]);

        ReportSchedule::query()->updateOrCreate(['id' => 'sch_001'], [
            'name' => 'Weekly command performance',
            'report_type' => 'analytics',
            'filters' => ['period' => '7d'],
            'frequency' => 'weekly',
            'recipients' => ['commissioner@npf.gov.ng', 'ops@npf.gov.ng'],
            'next_run_at' => now()->addWeek()->startOfWeek()->setTime(7, 0),
            'active' => true,
            'created_by' => 'off_ada',
        ]);
        ReportSchedule::query()->updateOrCreate(['id' => 'sch_002'], [
            'name' => 'Daily watchlist digest',
            'report_type' => 'watchlist',
            'filters' => ['risk' => 'critical'],
            'frequency' => 'daily',
            'recipients' => ['ops-room@npf.gov.ng'],
            'next_run_at' => now()->addDay()->setTime(6, 0),
            'active' => true,
            'created_by' => 'off_fatima',
        ]);
    }
}
