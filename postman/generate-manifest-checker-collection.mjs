import fs from 'node:fs';
import path from 'node:path';

const base = '{{baseUrl}}/api/v1/manifest-checker';
const auth = { key: 'Authorization', value: 'Bearer {{token}}' };
const json = { key: 'Content-Type', value: 'application/json' };

const ok = (data, message = 'OK') => ({ status: true, message, data });
const fail = (message, code = 'VALIDATION_FAILED', details = {}) => ({
  status: false,
  message,
  data: { code, details, requestId: 'req_01JEMFEXAMPLE000000000000' },
});

const officer = {
  officerId: 'off_ada',
  serviceNumber: 'NPF-88214',
  rank: 'Deputy Superintendent',
  fullName: 'Adaemu Y. Bello',
  email: 'ada.bello@npf.gov.ng',
  phone: '+2348030000001',
  unit: 'Manifest Enforcement',
  role: 'state_commander',
  roleLabel: 'State Commander',
  status: 'on_duty',
  language: 'en',
  stateCode: 'LA',
  stateName: 'Lagos',
  lgaId: 'lga_la_2',
  lgaName: 'Eti-Osa',
  dutyPoint: { dutyPointId: 'dp_lekki', name: 'Lekki Phase 1 Checkpoint', type: 'other' },
  checksToday: 702,
  activeGrants: 1,
  permissions: ['dashboard:read', 'alerts:read', 'alerts:respond', 'manifests:read', 'manifests:check', 'watchlist:read', 'watchlist:manage', 'incidents:read', 'incidents:manage', 'cases:read', 'cases:manage', 'officers:read', 'officers:manage', 'grants:manage', 'duty:read', 'duty:manage', 'analytics:read', 'audit:read', 'settings:manage', 'exports:create'],
  lastActiveAt: '2026-11-19T09:42:00+01:00',
};

const alert = (overrides = {}) => ({
  alertId: 'al_001',
  alertNumber: 'AL-26-00001',
  type: 'critical',
  title: 'NIN match 98% — Adaemu Y. Bello',
  summary: 'Watchlist match confirmed at the Lekki checkpoint.',
  confidence: 98,
  mode: 'road',
  route: 'Lagos → Ibadan',
  status: 'open',
  detectedAt: '2026-11-19T09:30:04+01:00',
  verifiedAt: '2026-11-19T09:30:16+01:00',
  acknowledgedAt: null,
  slaSeconds: 120,
  slaRemainingSeconds: 27,
  slaLabel: 'SLA 00:27',
  subject: {
    watchlistEntryId: 'wl_001',
    entryNumber: 'WL-26-0001',
    fullName: 'Adaemu Y. Bello',
    nin: '12345678901',
    phone: '+2348031110001',
    risk: 'critical',
    reason: 'Armed robbery suspect',
    source: 'NPF CID',
    expiresAt: '2026-12-19T09:47:23+01:00',
  },
  manifest: {
    manifestId: 'mf_001',
    manifestNumber: 'MF-26-00001',
    operator: 'ABC Transport',
    route: 'Lagos → Ibadan',
  },
  responder: null,
  resolution: null,
  ...overrides,
});

const alertDetail = {
  ...alert(),
  enforcementActions: [
    { action: 'dismiss', label: 'Dismiss as false positive' },
    { action: 'escalate', label: 'Escalate to incident' },
    { action: 'assign', label: 'Assign responder' },
  ],
  checks: [
    { checkId: 'chk_1_nin_verification', type: 'nin_verification', status: 'clear', result: 'NIN verified against NIMC', performedAt: '2026-11-19T09:30:00+01:00' },
    { checkId: 'chk_1_watchlist_screening', type: 'watchlist_screening', status: 'flagged', result: 'Watchlist match confirmed', performedAt: '2026-11-19T09:30:00+01:00' },
    { checkId: 'chk_1_plate_lookup', type: 'plate_lookup', status: 'clear', result: 'Plate lookup clear', performedAt: '2026-11-19T09:30:00+01:00' },
    { checkId: 'chk_1_headcount', type: 'headcount', status: 'clear', result: 'Headcount completed', performedAt: '2026-11-19T09:30:00+01:00' },
  ],
  actions: [
    { action: 'detected', note: 'Automated screening raised this alert.', reason: null, actor: 'System', createdAt: '2026-11-19T09:30:04+01:00' },
  ],
  manifestDetail: {
    manifestId: 'mf_001',
    manifestNumber: 'MF-26-00001',
    mode: 'road',
    operator: 'ABC Transport',
    route: 'Lagos → Ibadan',
    vehicleRegistration: 'LSD-401-XY',
    departedAt: '2026-11-19T09:10:00+01:00',
    expectedArrivalAt: '2026-11-19T11:10:00+01:00',
    passengers: [
      { fullName: 'Adaemu Y. Bello', nin: '12345678901', seat: '1', verdict: 'flagged' },
      { fullName: 'Ngozi Okafor', nin: '10000000101', seat: '2', verdict: 'clear' },
    ],
  },
  incident: null,
};

const manifestSummary = {
  manifestId: 'mf_001',
  manifestNumber: 'MF-26-00001',
  mode: 'road',
  operator: 'ABC Transport',
  vehicleRegistration: 'LSD-401-XY',
  driverName: 'Tunde Balogun',
  route: { origin: 'Lagos', destination: 'Ibadan' },
  seats: 18,
  passengerCount: 6,
  checkedCount: 6,
  verdict: 'flagged',
  checkedBy: 'Adaemu Y. Bello',
  checkedAt: '2026-11-19T09:02:23+01:00',
  dutyPoint: 'Lekki Phase 1 Checkpoint',
  departedAt: '2026-11-19T08:42:23+01:00',
  expectedArrivalAt: '2026-11-19T11:02:23+01:00',
};

const manifestDetail = {
  ...manifestSummary,
  journey: [
    { key: 'departed', label: 'Departed', place: 'Lagos', time: '2026-11-19T08:42:23+01:00', status: 'done' },
    { key: 'checkpoint', label: 'Mile 1 checkpoint', place: 'Lekki Phase 1 Checkpoint', time: '2026-11-19T09:02:23+01:00', status: 'done' },
    { key: 'junction', label: 'Interchange junction', place: 'En route', time: null, status: 'pending' },
    { key: 'arrivals', label: 'Arrivals', place: 'Ibadan', time: '2026-11-19T11:02:23+01:00', status: 'eta' },
  ],
  passengers: [
    { passengerId: 'pax_1_0', fullName: 'Ngozi Okafor', nin: '10000000100', seat: '1', verdict: 'clear', identityConfidence: 100 },
    { passengerId: 'pax_1_1', fullName: 'Adaemu Y. Bello', nin: '12345678901', seat: '2', verdict: 'flagged', identityConfidence: 98 },
  ],
  checks: alertDetail.checks,
  notes: null,
  actions: ['add_note', 'export', 'escalate'],
};

const watchlistEntry = (overrides = {}) => ({
  watchlistEntryId: 'wl_001',
  entryNumber: 'WL-26-0001',
  type: 'person',
  risk: 'critical',
  reason: 'Armed robbery suspect',
  source: 'NPF CID',
  fullName: 'Adaemu Y. Bello',
  nin: '12345678901',
  phone: '+2348031110001',
  plateNumber: null,
  dob: '1991-04-12',
  nationality: 'Nigerian',
  address: '14 Adeola Odeku Street, Victoria Island, Lagos',
  status: 'active',
  expiresAt: '2026-12-19T09:47:23+01:00',
  notes: 'Seeded demo record for the Manifest Checker operations room.',
  createdAt: '2026-11-19T09:47:23+01:00',
  ...overrides,
});

const watchlistDetail = {
  ...watchlistEntry(),
  validity: { status: 'active', expiresAt: '2026-12-19T09:47:23+01:00', editWindow: '6-digit edit code required' },
  actions: ['edit', 'export', 'remove', 'open_case', 'escalate'],
  travelHistory: [
    { manifestNumber: 'MF-26-00001', manifestId: 'mf_001', mode: 'road', route: 'Lagos → Ibadan', date: '2026-11-19T09:02:23+01:00', verdict: 'flagged' },
  ],
  linkedRecords: {
    alerts: [{ alertId: 'al_001', alertNumber: 'AL-26-00001', type: 'critical', status: 'open' }],
    incidents: [{ incidentId: 'inc_001', incidentNumber: 'INC-26-00001', status: 'assigned' }],
    cases: [{ caseId: 'case_001', caseNumber: 'CS-26-0001', status: 'open' }],
  },
  documents: [
    { documentId: 1, name: 'Court order.pdf', fileUrl: 'https://cdn.example.test/court-order.pdf', fileSize: 48213 },
  ],
};

const incidentSummary = {
  incidentId: 'inc_001',
  incidentNumber: 'INC-26-00001',
  title: 'Passenger identity discrepancy',
  type: 'manifest_verification',
  severity: 'critical',
  status: 'assigned',
  summary: 'Incident opened from the manifest verification queue for follow-up.',
  location: 'Lekki Phase 1 Checkpoint',
  manifest: { manifestId: 'mf_001', manifestNumber: 'MF-26-00001' },
  responder: { officerId: 'off_grace', fullName: 'Grace Nwosu' },
  reportedAt: '2026-11-19T08:10:00+01:00',
  acknowledgedAt: '2026-11-19T08:18:00+01:00',
  resolvedAt: null,
};

const caseSummary = (overrides = {}) => ({
  caseId: 'case_001',
  caseNumber: 'CS-26-0001',
  title: 'Investigation — Passenger identity discrepancy',
  status: 'open',
  severity: 'critical',
  owner: { officerId: 'off_emeka', fullName: 'Emeka Okafor' },
  openedAt: '2026-11-19T09:10:00+01:00',
  closedAt: null,
  dueDate: '2026-11-27',
  approvalStatus: 'none',
  shareStatus: 'none',
  linkedRecords: [
    { type: 'incident', id: 'inc_001', number: 'INC-26-00001' },
    { type: 'manifest', id: 'mf_001' },
    { type: 'watchlistEntry', id: 'wl_001' },
  ],
  ...overrides,
});

const caseDetail = {
  ...caseSummary(),
  summary: 'Case workspace for the INC-26-00001 follow-up. Evidence and approvals are tracked here.',
  approvedBy: null,
  approvedAt: null,
  metadata: { createdFrom: 'incident', convertedBy: 'Emeka Okafor' },
  evidence: [
    { evidenceId: 'evd_1_1', type: 'document', label: 'Passenger manifest extract', fileUrl: 'https://example.test/signed-evidence', fileSize: 48213, uploadedAt: '2026-11-19T09:10:00+01:00' },
  ],
  activity: [
    { eventId: 2, action: 'note', note: 'Witness statements collected; CCTV footage requested from the operator.', actor: 'Grace Nwosu', createdAt: '2026-11-19T10:10:00+01:00' },
    { eventId: 1, action: 'created', note: 'Case opened from incident INC-26-00001', actor: 'Emeka Okafor', createdAt: '2026-11-19T09:10:00+01:00' },
  ],
  actions: ['update', 'add_note', 'add_evidence', 'submit_closure', 'approve_closure', 'send_back', 'request_reopen', 'share', 'close'],
};

const officerSummary = {
  officerId: 'off_ada',
  serviceNumber: 'NPF-88214',
  rank: 'Deputy Superintendent',
  fullName: 'Adaemu Y. Bello',
  unit: 'Manifest Enforcement',
  role: 'state_commander',
  status: 'on_duty',
  dutyPoint: { dutyPointId: 'dp_lekki', name: 'Lekki Phase 1 Checkpoint' },
  lgaName: 'Eti-Osa',
  grant: { grantNumber: 'GR-26-0001', status: 'active' },
  grantStatus: 'active',
  lastActiveAt: '2026-11-19T09:42:00+01:00',
  checksToday: 702,
};

const grant = (overrides = {}) => ({
  grantId: 'gr_001',
  grantNumber: 'GR-26-0001',
  officer: { officerId: 'off_ada', fullName: 'Adaemu Y. Bello', serviceNumber: 'NPF-88214' },
  template: { templateId: 'grt_supervisor', name: 'Supervisor Review' },
  scope: 'Statewide',
  modes: ['road', 'sea', 'air', 'rail'],
  startsAt: '2026-11-19T08:00:00+01:00',
  endsAt: '2026-11-22T08:00:00+01:00',
  status: 'active',
  revokedAt: null,
  revokeReason: null,
  notes: null,
  ...overrides,
});

const dutyPoint = (overrides = {}) => ({
  dutyPointId: 'dp_lekki',
  name: 'Lekki Phase 1 Checkpoint',
  type: 'other',
  stateCode: 'LA',
  stateName: 'Lagos',
  lgaId: 'lga_la_2',
  lgaName: 'Eti-Osa',
  address: 'Lekki Phase 1 Checkpoint, Nigeria',
  latitude: 6.4478,
  longitude: 3.4723,
  officers: 12,
  grants: 4,
  shift: '06:00–18:00',
  status: 'active',
  ...overrides,
});

const template = (overrides = {}) => ({
  templateId: 'grt_checkpoint',
  name: '24-hour Checkpoint',
  description: 'Standard field grant for motor parks and checkpoints.',
  scope: 'Checkpoint',
  modes: ['road'],
  durationHours: 24,
  accessCount: 6,
  status: 'active',
  ...overrides,
});

const exportExample = (overrides = {}) => ({
  exportId: 'exp_demo01',
  reportType: 'manifests',
  status: 'ready',
  format: 'csv',
  rowCount: 30,
  watermark: true,
  requires2fa: true,
  downloadUrl: 'https://api.example.test/api/v1/manifest-checker/media/exports/exp_demo01?expires=...&signature=...',
  expiresAt: '2026-11-19T10:05:00+01:00',
  createdAt: '2026-11-19T09:50:00+01:00',
  ...overrides,
});

const collection = {
  info: {
    _postman_id: 'e4d8a6f0-6c6f-4c9b-9a2f-6d1b0f2c7a11',
    name: 'NPF Manifest Checker API v1',
    description: 'Versioned Manifest Checker API built from the supplied operations-room designs. All responses use the {status, message, data} envelope. Sign in first; the login request saves the bearer token to the collection variable.',
    schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
  },
  variable: [
    { key: 'baseUrl', value: 'http://localhost:8000' },
    { key: 'token', value: '' },
    { key: 'stateCode', value: 'LA' },
    { key: 'alertId', value: 'al_001' },
    { key: 'manifestId', value: 'mf_001' },
    { key: 'watchlistId', value: 'wl_001' },
    { key: 'incidentId', value: 'inc_001' },
    { key: 'caseId', value: 'case_001' },
    { key: 'officerId', value: 'off_ada' },
    { key: 'grantId', value: 'gr_001' },
    { key: 'dutyPointId', value: 'dp_lekki' },
    { key: 'exportId', value: 'exp_demo01' },
  ],
  item: [],
};

let sequence = 0;
function request(name, method, urlPath, { body, example, description, test, headers } = {}) {
  sequence++;
  const requestHeaders = [{ key: 'Accept', value: 'application/json' }, json, ...(headers || [])];
  const responseHeaders = [{ key: 'Content-Type', value: 'application/json' }];
  requestHeaders.push(auth);

  return {
    name,
    event: test ? [{ listen: 'test', script: { type: 'text/javascript', exec: test.trim().split('\n') } }] : [],
    request: {
      method,
      header: requestHeaders,
      body: body === undefined ? undefined : { mode: 'raw', raw: JSON.stringify(body, null, 2), options: { raw: { language: 'json' } } },
      url: `${base}${urlPath}`,
      description: description || '',
    },
    response: [
      {
        name: `${method === 'GET' ? '200' : '200'} — success example`,
        originalRequest: {
          method,
          header: requestHeaders,
          url: `${base}${urlPath}`,
          body: body === undefined ? undefined : { mode: 'raw', raw: JSON.stringify(body, null, 2) },
        },
        status: 'OK',
        code: 200,
        _postman_previewlanguage: 'json',
        header: responseHeaders,
        cookie: [],
        body: JSON.stringify(example ?? ok({}), null, 2),
      },
    ],
  };
}

const folder = (name, description, items) => ({ name, description, item: items });

collection.item.push(
  folder('Authentication', 'Sign in and manage the officer session.', [
    request('Sign in', 'POST', '/auth/login', {
      body: { serviceNumber: 'NPF-88214', password: 'Password@123' },
      description: 'Use a seeded officer account. The test script stores the returned bearer token.',
      test: `pm.test('signed in', () => pm.response.to.have.status(200));
const payload = pm.response.json();
pm.test('status envelope', () => pm.expect(payload.status).to.eql(true));
if (payload.data && payload.data.token) {
  pm.collectionVariables.set('token', payload.data.token);
}`,
      example: ok({
        token: '6f5a3e6ef5c94bb7b4bcb5a2ce872271d0f7b1e8e71d4e2e9d4c7f0a8b9c1d2e',
        expiresAt: '2026-11-19T17:47:23+01:00',
        officer,
      }, 'Signed in to the Manifest Checker.'),
    }),
    request('Current officer', 'GET', '/auth/me', {
      example: ok(officer, 'Officer profile.'),
    }),
    request('Sign out', 'POST', '/auth/logout', {
      example: ok(null, 'Signed out.'),
    }),
  ]),
  folder('Reference data', 'Modes, statuses, role permissions, states and LGAs.', [
    request('Get reference data', 'GET', '/meta', {
      example: ok({
        officer: { officerId: 'off_ada', fullName: 'Adaemu Y. Bello', role: 'state_commander', stateCode: 'LA', permissions: officer.permissions },
        modes: [{ key: 'road', label: 'Road' }, { key: 'sea', label: 'Sea' }, { key: 'air', label: 'Air' }, { key: 'rail', label: 'Rail' }, { key: 'hospitality', label: 'Hospitality' }],
        verdicts: [{ key: 'clear', label: 'Clear' }, { key: 'flagged', label: 'Flagged' }, { key: 'mismatch', label: 'Mismatch' }, { key: 'unverified', label: 'Unverified' }],
        alertTypes: [{ key: 'critical', label: 'Critical' }, { key: 'flagged', label: 'Flagged' }, { key: 'mismatch', label: 'Mismatch' }, { key: 'unverified', label: 'Unverified' }],
        riskLevels: [{ key: 'critical', label: 'Critical' }, { key: 'high', label: 'High' }, { key: 'medium', label: 'Medium' }, { key: 'low', label: 'Low' }],
        severities: [{ key: 'critical', label: 'Critical' }, { key: 'high', label: 'High' }, { key: 'medium', label: 'Medium' }, { key: 'low', label: 'Low' }],
        caseStatuses: [{ key: 'open', label: 'Open' }, { key: 'in_review', label: 'In Review' }, { key: 'awaiting_approval', label: 'Awaiting Approval' }, { key: 'closed', label: 'Closed' }],
        dutyPointTypes: [{ key: 'motor_park', label: 'Motor Park' }, { key: 'jetty', label: 'Jetty' }, { key: 'airport', label: 'Airport' }, { key: 'rail', label: 'Rail' }, { key: 'hotel', label: 'Hotel' }, { key: 'other', label: 'Other' }],
        periods: [{ key: '24h', label: 'Last 24 hours' }, { key: '7d', label: 'Last 7 days' }, { key: '30d', label: 'Last 30 days' }, { key: 'quarter', label: 'Last quarter' }],
        states: [{ code: 'LA', name: 'Lagos', lgaCount: 5, lgas: [{ lgaId: 'lga_la_1', name: 'Ikeja' }, { lgaId: 'lga_la_2', name: 'Eti-Osa' }] }],
      }, 'Manifest Checker reference data.'),
    }),
    request('Get states and LGAs', 'GET', '/meta/states', {
      example: ok([{ code: 'LA', name: 'Lagos', lgaCount: 5, lgas: [{ lgaId: 'lga_la_1', name: 'Ikeja' }] }], 'States and LGAs.'),
    }),
    request('Get system state guidance', 'GET', '/meta/system-states', {
      example: ok({
        loading: { key: 'loading', title: 'Loading', description: 'Use when a request is in flight and no cached data exists.' },
        empty: { key: 'empty', title: 'No records', description: 'Use when the request succeeded with zero results.' },
        error: { key: 'error', title: 'Something went wrong', description: 'Use with a retry action for 5xx and network failures.' },
        no_permission: { key: 'no_permission', title: 'No permission', description: 'Use with the required permission and a request-access action.' },
        session_expired: { key: 'session_expired', title: 'Session expired', description: 'Return to sign-in; the API returns 401 UNAUTHENTICATED.' },
      }, 'System state guidance.'),
    }),
  ]),
  folder('Dashboard', 'Command overview and global search.', [
    request('Command dashboard', 'GET', '/dashboard?stateCode=LA&period=24h', {
      test: `pm.test('dashboard loaded', () => pm.response.to.have.status(200));
pm.test('metrics present', () => pm.expect(pm.response.json().data.metrics).to.be.an('object'));`,
      example: ok({
        scope: { stateCode: 'LA', lgaId: null, mode: null, period: '24h', liveAt: '2026-11-19T09:50:00+01:00' },
        metrics: { activeManifests: 30, watchlistHits: 17, openIncidents: 9, officersOnDuty: 8, avgAckSeconds: 108, avgAckLabel: '1m 48s' },
        liveAlerts: [alert(), alert({ alertId: 'al_002', alertNumber: 'AL-26-00002', type: 'flagged', title: 'Plate mismatch at Ojota', confidence: 91 })],
        manifestsByMode: [{ key: 'road', label: 'Road', count: 18 }, { key: 'sea', label: 'Sea', count: 4 }, { key: 'air', label: 'Air', count: 4 }, { key: 'rail', label: 'Rail', count: 2 }, { key: 'hospitality', label: 'Hospitality', count: 2 }],
        incidentsBySeverity: [{ key: 'critical', label: 'Critical', count: 4 }, { key: 'high', label: 'High', count: 5 }, { key: 'medium', label: 'Medium', count: 3 }, { key: 'low', label: 'Low', count: 2 }],
        coverageGaps: [{ dutyPointId: 'dp_hotel', name: 'Eko Hotel Hospitality Desk', type: 'hotel', reason: 'No officer on duty', status: 'critical', officers: 0, conflicts: 0 }],
        latestWatchlistHits: [alert()],
      }, 'Command dashboard.'),
    }),
    request('Global search', 'GET', '/dashboard/search?q=MF-26-00001', {
      example: ok({
        manifests: [{ type: 'manifest', id: 'mf_001', title: 'MF-26-00001', subtitle: 'ABC Transport · Lagos → Ibadan', verdict: 'flagged' }],
        watchlist: [],
        incidents: [],
        cases: [],
        officers: [],
      }, 'Search results.'),
    }),
  ]),
  folder('Live alerts', 'Alert queue, hit detail and enforcement actions.', [
    request('List alerts', 'GET', '/alerts?type=critical&period=24h', {
      test: `pm.test('alerts loaded', () => pm.response.to.have.status(200));
const first = pm.response.json().data.data[0];
if (first) pm.collectionVariables.set('alertId', first.alertId);`,
      example: ok({ data: [alert()], pagination: { page: 1, pageSize: 25, totalItems: 17, totalPages: 1 } }, 'Alert queue.'),
    }),
    request('Get alert detail', 'GET', '/alerts/{{alertId}}', {
      example: ok(alertDetail, 'Alert detail.'),
    }),
    request('Acknowledge alert', 'POST', '/alerts/{{alertId}}/acknowledge', {
      example: ok(alert({ status: 'acknowledged', acknowledgedAt: '2026-11-19T09:31:00+01:00' }), 'Alert acknowledged.'),
    }),
    request('Dismiss as false positive', 'POST', '/alerts/{{alertId}}/dismiss', {
      body: { reason: 'false_positive', note: 'Identity confirmed against the operator manifest.' },
      example: ok(alert({ status: 'dismissed', resolution: 'Identity confirmed against the operator manifest.' }), 'Alert dismissed as a false positive.'),
    }),
    request('Escalate to incident', 'POST', '/alerts/{{alertId}}/escalate', {
      body: { severity: 'high', notes: 'Escalating after NIN confirmation.', assigneeId: 'off_grace' },
      example: ok(alert({ status: 'escalated', incident: { incidentId: 'inc_001', incidentNumber: 'INC-26-00001', status: 'assigned' } }), 'Alert escalated to an incident.'),
    }),
    request('Assign responder', 'POST', '/alerts/{{alertId}}/assign', {
      body: { officerId: 'off_grace', actionType: 'radio', note: 'Responder dispatched to the checkpoint.' },
      example: ok(alert({ status: 'assigned', responder: { officerId: 'off_grace', fullName: 'Grace Nwosu', serviceNumber: 'NPF-44310' } }), 'Responder assigned.'),
    }),
  ]),
  folder('Manifests', 'Manifest checks, passenger list, notes and exports.', [
    request('List manifests', 'GET', '/manifests?verdict=flagged&period=24h', {
      test: `pm.test('manifests loaded', () => pm.response.to.have.status(200));
const first = pm.response.json().data.data[0];
if (first) pm.collectionVariables.set('manifestId', first.manifestId);`,
      example: ok({ data: [manifestSummary], pagination: { page: 1, pageSize: 25, totalItems: 30, totalPages: 2 } }, 'Manifest checks.'),
    }),
    request('New manifest check', 'POST', '/manifests/checks', {
      body: {
        mode: 'road',
        operator: 'ABC Transport',
        routeOrigin: 'Lagos',
        routeDestination: 'Ibadan',
        vehicleRegistration: 'LSD-401-XY',
        driverName: 'Tunde Balogun',
        seats: 18,
        passengers: [
          { fullName: 'Adaemu Y. Bello', nin: '12345678901', seat: '1' },
          { fullName: 'Ngozi Okafor', nin: '10000000100', seat: '2' },
        ],
      },
      test: `pm.test('manifest checked', () => pm.response.to.have.status(201));
const payload = pm.response.json();
if (payload.data && payload.data.manifestId) pm.collectionVariables.set('manifestId', payload.data.manifestId);`,
      example: ok(manifestDetail, 'Manifest checked.'),
    }),
    request('Get manifest detail', 'GET', '/manifests/{{manifestId}}', {
      example: ok(manifestDetail, 'Manifest detail.'),
    }),
    request('List manifest passengers', 'GET', '/manifests/{{manifestId}}/passengers?page=1&pageSize=25', {
      example: ok({ data: manifestDetail.passengers, pagination: { page: 1, pageSize: 25, totalItems: 6, totalPages: 1 } }, 'Manifest passengers.'),
    }),
    request('Add manifest note', 'POST', '/manifests/{{manifestId}}/notes', {
      body: { note: 'Supervisor reviewed the flagged passenger.' },
      example: ok({ ...manifestDetail, notes: '[2026-11-19T09:52:00+01:00] Adaemu Y. Bello: Supervisor reviewed the flagged passenger.' }, 'Note added to manifest.'),
    }),
    request('Export manifest', 'POST', '/manifests/{{manifestId}}/export', {
      test: `pm.test('export ready', () => pm.response.to.have.status(200));
const payload = pm.response.json();
if (payload.data && payload.data.exportId) pm.collectionVariables.set('exportId', payload.data.exportId);`,
      example: ok(exportExample(), 'Manifest export ready.'),
    }),
  ]),
  folder('Watchlist', 'Entries, bulk import, subscriptions, documents and person profile.', [
    request('List watchlist entries', 'GET', '/watchlist?risk=critical&status=active', {
      test: `pm.test('watchlist loaded', () => pm.response.to.have.status(200));
const first = pm.response.json().data.data[0];
if (first) pm.collectionVariables.set('watchlistId', first.watchlistEntryId);`,
      example: ok({ data: [watchlistEntry()], pagination: { page: 1, pageSize: 25, totalItems: 10, totalPages: 1 } }, 'Watchlist entries.'),
    }),
    request('Add watchlist entry', 'POST', '/watchlist', {
      body: { type: 'person', risk: 'high', reason: 'Person of interest', source: 'NPF CID', fullName: 'Musa Danjuma', nin: '22345678902', phone: '+2348031110003', expiresAt: '2027-01-18T09:47:23+01:00' },
      test: `pm.test('entry created', () => pm.response.to.have.status(201));
const payload = pm.response.json();
if (payload.data) pm.collectionVariables.set('watchlistId', payload.data.watchlistEntryId);`,
      example: ok(watchlistDetail, 'Watchlist entry created.'),
    }),
    request('Bulk import watchlist', 'POST', '/watchlist/import', {
      body: { entries: [{ type: 'person', risk: 'medium', reason: 'Witness', source: 'NPF Legal', fullName: 'Bisi Ogunleye' }, { type: 'vehicle', risk: 'low', reason: 'Vehicle check', source: 'VIO', plateNumber: 'KJA-211-AA' }] },
      example: ok({ created: 2, skipped: 0, errors: [] }, 'Watchlist import completed.'),
    }),
    request('Escalate watchlist entry', 'POST', '/watchlist/{{watchlistId}}/escalate', {
      body: { severity: 'high', location: 'Lekki Phase 1 Checkpoint', notes: 'Intelligence confirmed the match.', assigneeId: 'off_grace' },
      example: ok(incidentSummary, 'Watchlist entry escalated to an incident.'),
    }),
    request('Open case from watchlist', 'POST', '/watchlist/{{watchlistId}}/open-case', {
      body: { title: 'Watchlist investigation', severity: 'high', ownerId: 'off_emeka', dueDate: '2026-11-27', summary: 'Case opened from the watchlist profile.' },
      example: ok(caseSummary(), 'Case opened from watchlist entry.'),
    }),
    request('Get watchlist profile', 'GET', '/watchlist/{{watchlistId}}', {
      example: ok(watchlistDetail, 'Watchlist entry detail.'),
    }),
    request('Edit watchlist entry', 'PUT', '/watchlist/{{watchlistId}}', {
      body: { verificationCode: '123456', risk: 'critical', notes: 'Escalated by intelligence.' },
      example: ok({ ...watchlistDetail, risk: 'critical', notes: 'Escalated by intelligence.' }, 'Watchlist entry updated.'),
    }),
    request('Remove watchlist entry', 'DELETE', '/watchlist/{{watchlistId}}', {
      body: { verificationCode: '123456', reason: 'Record cleared.' },
      example: ok(null, 'Watchlist entry removed.'),
    }),
    request('List subscriptions', 'GET', '/watchlist/subscriptions', {
      example: ok([{ subscriptionId: 'sub_001', subscriberName: 'Ops Room', agency: 'NPF', scope: 'Statewide', modes: ['road', 'sea'], notifyEmail: true, status: 'active', createdAt: '2026-11-19T09:47:23+01:00' }], 'Watchlist subscriptions and assignments.'),
    }),
    request('Create subscription', 'POST', '/watchlist/subscriptions', {
      body: { subscriberName: 'Ops Room', agency: 'NPF', scope: 'Statewide', modes: ['road', 'sea'], notifyEmail: true },
      example: ok([{ subscriptionId: 'sub_001', subscriberName: 'Ops Room', agency: 'NPF', scope: 'Statewide', modes: ['road', 'sea'], notifyEmail: true, status: 'active' }], 'Subscription created.'),
    }),
    request('List documents', 'GET', '/watchlist/documents', {
      example: ok({ data: [{ documentId: 1, entryNumber: 'WL-26-0001', entryName: 'Adaemu Y. Bello', name: 'Court order.pdf', fileUrl: 'https://cdn.example.test/court-order.pdf', fileSize: 48213 }], pagination: { page: 1, pageSize: 25, totalItems: 1, totalPages: 1 } }, 'Watchlist documents.'),
    }),
    request('Attach document', 'POST', '/watchlist/{{watchlistId}}/documents', {
      body: { name: 'Court order.pdf', fileUrl: 'https://cdn.example.test/court-order.pdf' },
      example: ok(watchlistDetail, 'Document added.'),
    }),
    request('Delete document', 'DELETE', '/watchlist/documents/1', {
      example: ok(null, 'Document removed.'),
    }),
  ]),
  folder('Incidents', 'Report, assign, convert to case, note and resolve.', [
    request('List incidents', 'GET', '/incidents?status=assigned&period=30d', {
      test: `pm.test('incidents loaded', () => pm.response.to.have.status(200));
const first = pm.response.json().data.data[0];
if (first) pm.collectionVariables.set('incidentId', first.incidentId);`,
      example: ok({ data: [incidentSummary], pagination: { page: 1, pageSize: 25, totalItems: 12, totalPages: 1 } }, 'Incidents.'),
    }),
    request('Report incident', 'POST', '/incidents', {
      body: { title: 'Passenger identity discrepancy', type: 'manifest_verification', severity: 'high', summary: 'Passenger could not be verified at the checkpoint.', location: 'Lekki Phase 1 Checkpoint' },
      test: `pm.test('incident created', () => pm.response.to.have.status(201));
const payload = pm.response.json();
if (payload.data) pm.collectionVariables.set('incidentId', payload.data.incidentId);`,
      example: ok({ ...incidentSummary, status: 'unsig', responder: null, acknowledgedAt: null }, 'Incident reported.'),
    }),
    request('Get incident detail', 'GET', '/incidents/{{incidentId}}', {
      example: ok({ ...incidentSummary, reportedBy: 'Emeka Okafor', metadata: { source: 'manual' }, timeline: [{ eventId: 1, type: 'created', description: 'Incident reported by Emeka Okafor', actor: 'Emeka Okafor', createdAt: '2026-11-19T08:10:00+01:00' }], linkedRecords: { manifest: { manifestId: 'mf_001', manifestNumber: 'MF-26-00001' }, alert: { alertId: 'al_001', alertNumber: 'AL-26-00001' }, case: null }, actions: ['assign_responder', 'convert_to_case', 'add_note', 'resolve'] }, 'Incident detail.'),
    }),
    request('Assign incident responder', 'POST', '/incidents/{{incidentId}}/assign', {
      body: { officerId: 'off_grace', note: 'Field response required.' },
      example: ok(incidentSummary, 'Responder assigned to incident.'),
    }),
    request('Convert incident to case', 'POST', '/incidents/{{incidentId}}/convert-to-case', {
      body: { caseTitle: 'Identity discrepancy investigation', ownerId: 'off_emeka', dueDate: '2026-11-27' },
      test: `pm.test('case created', () => pm.response.to.have.status(201));
const payload = pm.response.json();
if (payload.data) pm.collectionVariables.set('caseId', payload.data.caseId);`,
      example: ok(caseSummary(), 'Incident converted to a case.'),
    }),
    request('Add incident note', 'POST', '/incidents/{{incidentId}}/notes', {
      body: { note: 'Field team dispatched to the operator office.' },
      example: ok(incidentSummary, 'Note added to incident.'),
    }),
    request('Resolve incident', 'POST', '/incidents/{{incidentId}}/resolve', {
      body: { note: 'Identity confirmed and passenger released.' },
      example: ok({ ...incidentSummary, status: 'resolved', resolvedAt: '2026-11-19T10:00:00+01:00' }, 'Incident resolved.'),
    }),
  ]),
  folder('Cases', 'Case board, workspace, evidence and approval workflow.', [
    request('List cases', 'GET', '/cases?status=open', {
      test: `pm.test('cases loaded', () => pm.response.to.have.status(200));
const first = pm.response.json().data.data[0];
if (first) pm.collectionVariables.set('caseId', first.caseId);`,
      example: ok({ board: { open: 2, in_review: 2, awaiting_approval: 2, closed: 2 }, data: [caseSummary()], pagination: { page: 1, pageSize: 25, totalItems: 8, totalPages: 1 } }, 'Cases.'),
    }),
    request('Create case', 'POST', '/cases', {
      body: { title: 'Identity discrepancy investigation', severity: 'high', ownerId: 'off_emeka', dueDate: '2026-11-27', summary: 'Case opened from the checkpoint referral.' },
      example: ok(caseDetail, 'Case created.'),
    }),
    request('Get case workspace', 'GET', '/cases/{{caseId}}', {
      example: ok(caseDetail, 'Case workspace.'),
    }),
    request('Update case', 'PUT', '/cases/{{caseId}}', {
      body: { severity: 'critical', summary: 'Updated after supervisor review.' },
      example: ok({ ...caseDetail, severity: 'critical', summary: 'Updated after supervisor review.' }, 'Case updated.'),
    }),
    request('Get case activity', 'GET', '/cases/{{caseId}}/activity', {
      example: ok({ activity: caseDetail.activity, evidence: caseDetail.evidence }, 'Case activity and evidence.'),
    }),
    request('Add case note', 'POST', '/cases/{{caseId}}/notes', {
      body: { note: 'Witness statement collected.' },
      example: ok(caseDetail, 'Case note added.'),
    }),
    request('Add case evidence', 'POST', '/cases/{{caseId}}/evidence', {
      body: { type: 'document', label: 'Manifest extract', fileUrl: 'https://cdn.example.test/manifest.pdf' },
      example: ok(caseDetail, 'Evidence added.'),
    }),
    request('Submit case for closure', 'POST', '/cases/{{caseId}}/submit-closure', {
      body: { note: 'All evidence reviewed.', shareWithDss: true },
      example: ok(caseSummary({ status: 'awaiting_approval', approvalStatus: 'requested' }), 'Case submitted for closure approval.'),
    }),
    request('Approve case closure', 'POST', '/cases/{{caseId}}/approve-closure', {
      body: { note: 'Approved by supervisor.', shareWithAgency: true },
      example: ok(caseSummary({ status: 'closed', approvalStatus: 'approved', closedAt: '2026-11-19T11:00:00+01:00' }), 'Case closure approved.'),
    }),
    request('Send case back', 'POST', '/cases/{{caseId}}/send-back', {
      body: { reason: 'Please attach the operator CCTV response before closure.' },
      example: ok(caseSummary({ status: 'in_review', approvalStatus: 'returned' }), 'Case sent back to the owner.'),
    }),
    request('Request case reopen', 'POST', '/cases/{{caseId}}/request-reopen', {
      body: { reason: 'New witness statement received.' },
      example: ok(caseSummary({ status: 'in_review', approvalStatus: 'requested' }), 'Case reopen requested.'),
    }),
    request('Share case with partner agency', 'POST', '/cases/{{caseId}}/share', {
      body: { agency: 'DSS', expiresAt: '2026-11-26T09:50:00+01:00', code: '123456' },
      example: ok(caseSummary({ shareStatus: 'active' }), 'Case shared.'),
    }),
    request('Close case', 'POST', '/cases/{{caseId}}/close', {
      body: { note: 'Case closed after final review.' },
      example: ok(caseSummary({ status: 'closed', approvalStatus: 'approved', closedAt: '2026-11-19T11:00:00+01:00' }), 'Case closed.'),
    }),
  ]),
  folder('Officers & devices', 'Officer directory, performance, suspension and device approvals.', [
    request('List officers', 'GET', '/officers?status=on_duty', {
      test: `pm.test('officers loaded', () => pm.response.to.have.status(200));
const first = pm.response.json().data.data[0];
if (first) pm.collectionVariables.set('officerId', first.officerId);`,
      example: ok({ data: [officerSummary], pagination: { page: 1, pageSize: 25, totalItems: 10, totalPages: 1 } }, 'Officers and access.'),
    }),
    request('Officer performance', 'GET', '/officers/{{officerId}}/performance', {
      example: ok({
        officer: officerSummary,
        metrics: { checks: 702, hitsConfirmed: 14, avgResponseSeconds: 161, avgResponseLabel: '2m 41s', hitRate: 95 },
        checksPerDay: [{ date: '2026-11-13', checks: 88 }, { date: '2026-11-14', checks: 101 }, { date: '2026-11-15', checks: 94 }, { date: '2026-11-16', checks: 112 }, { date: '2026-11-17', checks: 106 }, { date: '2026-11-18', checks: 98 }, { date: '2026-11-19', checks: 103 }],
        accessGrants: [{ grantNumber: 'GR-26-0001', scope: 'Statewide', status: 'active', endsAt: '2026-11-22T08:00:00+01:00' }],
        hitsHandled: [{ alertNumber: 'AL-26-00001', type: 'critical', outcome: 'escalated', responseSeconds: 56, date: '2026-11-19T09:30:04+01:00' }],
      }, 'Officer performance.'),
    }),
    request('Suspend officer', 'POST', '/officers/{{officerId}}/suspend', {
      body: { reason: 'Pending conduct review.' },
      example: ok({ officer: { ...officerSummary, status: 'suspended' }, metrics: { checks: 702, hitsConfirmed: 14, avgResponseSeconds: 161, avgResponseLabel: '2m 41s', hitRate: 95 } }, 'Officer account suspended.'),
    }),
    request('Reactivate officer', 'POST', '/officers/{{officerId}}/reactivate', {
      example: ok({ officer: { ...officerSummary, status: 'off_duty' }, metrics: { checks: 702, hitsConfirmed: 14, avgResponseSeconds: 161, avgResponseLabel: '2m 41s', hitRate: 95 } }, 'Officer account reactivated.'),
    }),
    request('List pending devices', 'GET', '/devices?status=pending', {
      example: ok({ data: [{ deviceId: 'dev_001', officer: { officerId: 'off_ada', fullName: 'Adaemu Y. Bello', serviceNumber: 'NPF-88214' }, deviceName: 'iPad Pro · Ops Room', platform: 'iPadOS 18', status: 'pending', requestedAt: '2026-11-19T09:32:00+01:00' }], pagination: { page: 1, pageSize: 25, totalItems: 2, totalPages: 1 } }, 'Device approvals.'),
    }),
    request('Approve device', 'POST', '/devices/dev_001/approve', {
      example: ok({ deviceId: 'dev_001', status: 'approved' }, 'Device approved.'),
    }),
    request('Block device', 'POST', '/devices/dev_002/block', {
      body: { reason: 'Device reported lost.' },
      example: ok({ deviceId: 'dev_002', status: 'blocked' }, 'Device blocked.'),
    }),
  ]),
  folder('Access grants', 'Grant list, conflict check, revocation and templates.', [
    request('List access grants', 'GET', '/access-grants?status=active', {
      test: `pm.test('grants loaded', () => pm.response.to.have.status(200));
const first = pm.response.json().data.data[0];
if (first) pm.collectionVariables.set('grantId', first.grantId);`,
      example: ok({ data: [grant()], pagination: { page: 1, pageSize: 25, totalItems: 8, totalPages: 1 } }, 'Access grants.'),
    }),
    request('Create access grant', 'POST', '/access-grants', {
      body: { officerId: 'off_grace', scope: 'Lekki', modes: ['road'], startsAt: '2026-11-19T10:00:00+01:00', endsAt: '2026-11-20T10:00:00+01:00', notes: 'Checkpoint rotation.' },
      test: `pm.test('grant created', () => pm.response.to.have.status(201));
const payload = pm.response.json();
if (payload.data) pm.collectionVariables.set('grantId', payload.data.grantId);`,
      example: ok(grant({ grantId: 'gr_009', grantNumber: 'GR-26-0009', officer: { officerId: 'off_grace', fullName: 'Grace Nwosu', serviceNumber: 'NPF-44310' }, template: null, scope: 'Lekki' }), 'Access grant created.'),
    }),
    request('Check grant conflicts', 'POST', '/access-grants/conflicts', {
      body: { officerIds: ['off_grace'], startsAt: '2026-11-19T11:00:00+01:00', endsAt: '2026-11-19T13:00:00+01:00', modes: ['road'] },
      example: ok({ hasConflicts: true, conflicts: [{ grantNumber: 'GR-26-0004', scope: 'Lekki', startsAt: '2026-11-19T08:00:00+01:00', endsAt: '2026-11-19T20:00:00+01:00', modes: ['road'] }] }, 'Grant conflict check.'),
    }),
    request('Get access grant', 'GET', '/access-grants/{{grantId}}', {
      example: ok(grant(), 'Access grant detail.'),
    }),
    request('Revoke access grant', 'POST', '/access-grants/{{grantId}}/revoke', {
      body: { code: '123456', reason: 'Rotation complete.' },
      example: ok(grant({ status: 'revoked', revokedAt: '2026-11-19T10:30:00+01:00', revokeReason: 'Rotation complete.' }), 'Access grant revoked.'),
    }),
    request('List grant templates', 'GET', '/grant-templates', {
      example: ok([template(), template({ templateId: 'grt_airport', name: 'Airport Check', scope: 'Airport', modes: ['air'], durationHours: 12, accessCount: 4 })], 'Grant templates.'),
    }),
    request('Create grant template', 'POST', '/grant-templates', {
      body: { name: 'Checkpoint 24h', description: 'Standard field grant.', scope: 'Lekki', modes: ['road'], durationHours: 24, accessCount: 2 },
      example: ok([template()], 'Grant template created.'),
    }),
    request('Update grant template', 'PUT', '/grant-templates/grt_checkpoint', {
      body: { durationHours: 36, accessCount: 3 },
      example: ok([template({ durationHours: 36, accessCount: 3 })], 'Grant template updated.'),
    }),
    request('Delete grant template', 'DELETE', '/grant-templates/grt_checkpoint', {
      example: ok([], 'Grant template deleted.'),
    }),
  ]),
  folder('Duty points & roster', 'Duty point map, weekly roster, assignment and publishing.', [
    request('List duty points', 'GET', '/duty-points?stateCode=LA', {
      test: `pm.test('duty points loaded', () => pm.response.to.have.status(200));
const first = pm.response.json().data[0];
if (first) pm.collectionVariables.set('dutyPointId', first.dutyPointId);`,
      example: ok([dutyPoint(), dutyPoint({ dutyPointId: 'dp_airport', name: 'Murtala Muhammed Airport T1', type: 'airport', lgaName: 'Ikeja', officers: 8, grants: 3 })], 'Duty points.'),
    }),
    request('Create duty point', 'POST', '/duty-points', {
      body: { name: 'Victoria Island Checkpoint', type: 'other', lgaId: 'lga_la_2', address: 'Victoria Island, Lagos', latitude: 6.4281, longitude: 3.4219, shiftStart: '06:00', shiftEnd: '18:00' },
      example: ok({ dutyPointId: 'dp_011', name: 'Victoria Island Checkpoint' }, 'Duty point created.'),
    }),
    request('Get duty point', 'GET', '/duty-points/{{dutyPointId}}', {
      example: ok({ ...dutyPoint(), roster: [{ rosterId: 'ros_dp_lekki_20261117_morning', date: '2026-11-17', shift: 'morning', officer: 'Grace Nwosu', status: 'assigned', published: true }] }, 'Duty point detail.'),
    }),
    request('Update duty point', 'PUT', '/duty-points/{{dutyPointId}}', {
      body: { address: 'Lekki Phase 1, Lagos', status: 'active' },
      example: ok({ dutyPointId: 'dp_lekki', name: 'Lekki Phase 1 Checkpoint', status: 'active' }, 'Duty point updated.'),
    }),
    request('Get duty roster', 'GET', '/duty-roster?weekStart=2026-11-17&stateCode=LA', {
      example: ok({ weekStart: '2026-11-17', weekEnd: '2026-11-23', dutyPoints: [{ dutyPointId: 'dp_lekki', name: 'Lekki Phase 1 Checkpoint', type: 'other', lgaName: 'Eti-Osa', slots: [{ rosterId: 'ros_dp_lekki_20261117_morning', date: '2026-11-17', shift: 'morning', officer: 'Grace Nwosu', status: 'assigned', published: true }, { rosterId: 'ros_dp_lekki_20261117_afternoon', date: '2026-11-17', shift: 'afternoon', officer: null, status: 'vacant', published: true }] }], availableOfficers: [{ officerId: 'off_grace', fullName: 'Grace Nwosu', serviceNumber: 'NPF-44310', unit: 'Duty Operations', dutyPoint: 'Lekki Phase 1 Checkpoint' }] }, 'Duty roster.'),
    }),
    request('Assign roster slot', 'POST', '/duty-roster/assign', {
      body: { dutyPointId: 'dp_lekki', officerId: 'off_grace', date: '2026-11-19', shift: 'afternoon' },
      example: ok({ rosterId: 'ros_dp_lekki_20261119_afternoon', date: '2026-11-19', shift: 'afternoon', officer: 'Grace Nwosu', status: 'assigned' }, 'Roster slot assigned.'),
    }),
    request('Publish roster week', 'POST', '/duty-roster/publish', {
      body: { weekStart: '2026-11-17' },
      example: ok({ publishedSlots: 140 }, 'Roster published.'),
    }),
    request('Auto-fill roster vacancies', 'POST', '/duty-roster/auto-fill', {
      body: { weekStart: '2026-11-17' },
      example: ok({ assigned: 12, weekStart: '2026-11-17', weekEnd: '2026-11-23' }, 'Roster vacancies filled.'),
    }),
  ]),
  folder('Analytics & reports', 'Performance metrics, unit drill-down, coverage and scheduled reports.', [
    request('Analytics overview', 'GET', '/analytics?period=7d', {
      example: ok({
        period: '7d',
        metrics: { checks: 1284, hits: 17, hitRate: 1.3, avgResponseSeconds: 108, avgResponseLabel: '1m 48s', acknowledged: 14, ackRate: 82.4 },
        checksPerDay: [{ date: '2026-11-13', checks: 171, hits: 2 }, { date: '2026-11-14', checks: 188, hits: 3 }, { date: '2026-11-15', checks: 162, hits: 1 }, { date: '2026-11-16', checks: 179, hits: 4 }, { date: '2026-11-17', checks: 194, hits: 2 }, { date: '2026-11-18', checks: 186, hits: 3 }, { date: '2026-11-19', checks: 204, hits: 2 }],
        coverage: [{ dutyPointId: 'dp_hotel', name: 'Eko Hotel Hospitality Desk', type: 'hotel', reason: 'No officer on duty', officers: 0, conflicts: 0 }],
        leaderboard: [{ dutyPointId: 'dp_lekki', name: 'Lekki Phase 1 Checkpoint', checks: 302, hits: 5, hitRate: 1.7 }, { dutyPointId: 'dp_mile2', name: 'Mile 2 Motor Park', checks: 241, hits: 3, hitRate: 1.2 }],
      }, 'Analytics overview.'),
    }),
    request('Unit drill-down', 'GET', '/analytics/units/{{dutyPointId}}?period=7d', {
      example: ok({ dutyPoint: { dutyPointId: 'dp_lekki', name: 'Lekki Phase 1 Checkpoint', type: 'other', stateCode: 'LA', lgaName: 'Eti-Osa' }, metrics: { checks: 302, hits: 5, avgResponseSeconds: 96, avgResponseLabel: '1m 36s', ackRate: 80 }, checksPerDay: [{ date: '2026-11-13', checks: 41 }, { date: '2026-11-14', checks: 46 }], verdictMix: [{ key: 'clear', label: 'Clear', count: 279 }, { key: 'flagged', label: 'Flagged', count: 11 }, { key: 'mismatch', label: 'Mismatch', count: 6 }, { key: 'unverified', label: 'Unverified', count: 6 }], officers: [{ officerId: 'off_grace', fullName: 'Grace Nwosu', checks: 132, hits: 2 }] }, 'Unit drill-down.'),
    }),
    request('Coverage gaps', 'GET', '/analytics/coverage?stateCode=LA', {
      example: ok([{ dutyPointId: 'dp_hotel', name: 'Eko Hotel Hospitality Desk', type: 'hotel', reason: 'No officer on duty', officers: 0, conflicts: 0 }], 'Coverage gaps.'),
    }),
    request('Export analytics (2FA)', 'POST', '/analytics/export', {
      body: { code: '123456', watermark: true, period: '7d' },
      test: `pm.test('export ready', () => pm.response.to.have.status(200));
const payload = pm.response.json();
if (payload.data && payload.data.exportId) pm.collectionVariables.set('exportId', payload.data.exportId);`,
      example: ok(exportExample({ reportType: 'manifests', watermark: true, requires2fa: true }), 'Analytics export ready.'),
    }),
    request('List report schedules', 'GET', '/analytics/schedules', {
      example: ok([{ scheduleId: 'sch_001', name: 'Weekly command performance', reportType: 'analytics', frequency: 'weekly', recipients: ['commissioner@npf.gov.ng'], nextRunAt: '2026-11-23T07:00:00+01:00', active: true }], 'Scheduled reports.'),
    }),
    request('Create report schedule', 'POST', '/analytics/schedules', {
      body: { name: 'Weekly operations report', reportType: 'analytics', frequency: 'weekly', recipients: ['ops@npf.gov.ng'], nextRunAt: '2026-11-23T07:00:00+01:00' },
      example: ok([{ scheduleId: 'sch_003', name: 'Weekly operations report', reportType: 'analytics', frequency: 'weekly', recipients: ['ops@npf.gov.ng'], nextRunAt: '2026-11-23T07:00:00+01:00', active: true }], 'Report schedule created.'),
    }),
  ]),
  folder('Audit log', 'Searchable audit trail and watermarked export.', [
    request('List audit log', 'GET', '/audit-logs?period=24h', {
      example: ok({ data: [{ auditId: 12, time: '2026-11-19T09:47:00+01:00', actor: 'Adaemu Y. Bello', actorId: 'off_ada', action: 'manifest.checked', target: 'manifest', targetId: 'mf_001', detail: 'Checked manifest MF-26-00001', result: 'done', ip: '102.89.34.12' }], pagination: { page: 1, pageSize: 25, totalItems: 20, totalPages: 1 } }, 'Audit log.'),
    }),
    request('Export audit log', 'POST', '/audit-logs/export', {
      body: { reportType: 'audit', code: '123456', watermark: true, requires2fa: true },
      example: ok(exportExample({ reportType: 'audit', watermark: true, requires2fa: true }), 'Audit export ready.'),
    }),
  ]),
  folder('Notifications & settings', 'Notification centre, preferences, profile, sessions and help.', [
    request('List notifications', 'GET', '/notifications?filter=needs_action', {
      example: ok({ data: [{ notificationId: 'not_001', type: 'critical', title: 'Critical watchlist hit at Lekki', body: 'Adaemu Y. Bello matched at 98%. Respond within SLA.', needsAction: true, status: 'unread', link: 'alert', createdAt: '2026-11-19T09:30:00+01:00', readAt: null }], counts: { all: 7, needsAction: 4, alerts: 3, approvals: 2, expiries: 1 } }, 'Notifications.'),
    }),
    request('Mark notification read', 'POST', '/notifications/not_001/read', {
      example: ok({ notificationId: 'not_001', status: 'read' }, 'Notification marked read.'),
    }),
    request('Mark all notifications read', 'POST', '/notifications/read-all', {
      example: ok({ markedRead: 7 }, 'All notifications marked read.'),
    }),
    request('Get settings', 'GET', '/settings', {
      example: ok({ profile: officer, notifications: { criticalAlert: { inApp: true, sms: true, email: true, push: true }, flaggedManifest: { inApp: true, sms: false, email: true, push: true } }, preferenceOptions: { events: ['criticalAlert', 'flaggedManifest', 'incidentUpdate', 'caseApproval', 'grantExpiry', 'deviceApproval', 'weeklyDigest'], channels: ['inApp', 'sms', 'email', 'push'] }, sessions: [{ sessionId: 'ems_01', ip: '102.89.34.12', userAgent: 'Mozilla/5.0', createdAt: '2026-11-19T09:47:23+01:00', expiresAt: '2026-11-19T17:47:23+01:00', current: false }], languages: [{ code: 'en', label: 'English' }, { code: 'ha', label: 'Hausa' }], sessionTimeoutMinutes: 15 }, 'Settings.'),
    }),
    request('Update profile', 'PUT', '/settings/profile', {
      body: { fullName: 'Adaemu Y. Bello', phone: '+2348099999999', language: 'en' },
      example: ok({ profile: { ...officer, phone: '+2348099999999' }, notifications: {}, sessions: [], languages: [] }, 'Profile updated.'),
    }),
    request('Update notification preferences', 'PUT', '/settings/notifications', {
      body: { preferences: { criticalAlert: { inApp: true, sms: false, email: true, push: true } } },
      example: ok({ notifications: { criticalAlert: { inApp: true, sms: false, email: true, push: true }, flaggedManifest: { inApp: true, sms: false, email: true, push: true } } }, 'Notification preferences updated.'),
    }),
    request('List sessions', 'GET', '/settings/sessions', {
      example: ok([{ sessionId: 'ems_01', ip: '102.89.34.12', userAgent: 'Mozilla/5.0', createdAt: '2026-11-19T09:47:23+01:00', expiresAt: '2026-11-19T17:47:23+01:00', current: false }], 'Sessions and devices.'),
    }),
    request('Revoke session', 'DELETE', '/settings/sessions/ems_01', {
      example: ok(null, 'Session revoked.'),
    }),
    request('Help and support', 'GET', '/settings/help', {
      example: ok({ topics: [{ key: 'manifest_check', title: 'How to check a manifest', summary: 'Open Manifests, choose New check, enter the trip details and passenger list, then submit.' }, { key: 'alert_escalation', title: 'When to escalate an alert', summary: 'Escalate when a watchlist match is confirmed by NIN and an operational response is required.' }], support: { email: 'manifest-support@npf.gov.ng', phone: '+234 800 000 0000' } }, 'Help and support.'),
    }),
  ]),
  folder('Exports & signed media', 'Export job status and signed download endpoints.', [
    request('Get export job', 'GET', '/exports/{{exportId}}', {
      example: ok(exportExample(), 'Export job.'),
    }),
    request('Download signed export', 'GET', '/media/exports/{{exportId}}?expires=1763550000&signature=example', {
      description: 'Signed URL returned in data.downloadUrl. No bearer token is required; the signature is the authorization.',
      headers: [{ key: 'Authorization', value: '' }],
      example: ok({}, 'CSV file stream (text/csv).'),
    }),
    request('Download signed evidence', 'GET', '/media/evidence/evd_1_1?expires=1763550000&signature=example', {
      description: 'Signed URL returned inside a case workspace evidence item.',
      headers: [{ key: 'Authorization', value: '' }],
      example: ok({}, 'Evidence file stream.'),
    }),
  ]),
);

collection.info.description += `\n\nGenerated requests: ${sequence}. Seeded demo officer: NPF-88214 / Password@123 (change in production). Watchlist edit code: 123456.`;

fs.mkdirSync('postman', { recursive: true });
fs.writeFileSync(path.join('postman', 'Manifest-Checker-API.postman_collection.json'), JSON.stringify(collection, null, 2));
console.log(`Wrote ${sequence} requests across ${collection.item.length} folders.`);
