/**
 * Generates the NPIS Postman collection.
 *
 * Usage: node postman/generate-collection.mjs
 */
import fs from 'node:fs';

const base = '{{baseUrl}}';

const jsonHeader = { key: 'Content-Type', value: 'application/json' };
const idemHeader = { key: 'Idempotency-Key', value: '{{idempotencyKey}}' };
const ifMatchHeader = { key: 'If-Match', value: '{{ifMatch}}' };

const responseMessages = {
  'Get event': 'Event retrieved successfully',
  'Get published FAQs': 'FAQs retrieved successfully',
  'Get ranks': 'Ranks retrieved successfully',
  'Get states': 'States retrieved successfully',
  'Get commands': 'Commands retrieved successfully',
  'Get units': 'Units retrieved successfully',
  'Get airports': 'Airports retrieved successfully',
  'Get banks': 'Banks retrieved successfully',
  'Get protocol': 'Protocol retrieved successfully',
  'Initiate OTP verification': 'Verification code sent successfully',
  'Get verification status': 'Verification status retrieved successfully',
  'Resend verification code': 'Verification code resent successfully',
  'Confirm code and issue participant session': 'Contact verified successfully',
  'Submit registration': 'Registration submitted successfully',
  'Get my registration': 'Registration retrieved successfully',
  'Upload or replace my photo': 'Registration photo updated successfully',
  'Update itinerary': 'Travel itinerary updated successfully',
  'Update accommodation': 'Accommodation updated successfully',
  'Get my schedule': 'Schedule retrieved successfully',
  'Get my certificate': 'Certificate retrieved successfully',
  'Exchange organizer access code': 'Organizer access granted',
  'Exchange OIDC token for organizer session': 'Organizer session created successfully',
  'Change organizer access code': 'Organizer access code changed successfully',
  'List registrations': 'Registrations retrieved successfully',
  'Get registration': 'Registration retrieved successfully',
  'Review registration': 'Registration reviewed successfully',
  'Assign badge category': 'Badge category updated successfully',
  'Set or remove registration photo': 'Registration photo updated successfully',
  'Allocate accommodation': 'Accommodation allocation updated successfully',
  'Registration report': 'Registration report retrieved successfully',
  'Travel report': 'Travel report retrieved successfully',
  'Accommodation report': 'Accommodation report retrieved successfully',
  'Training report': 'Training report retrieved successfully',
  'Attendance report': 'Attendance report retrieved successfully',
  'List training sessions': 'Training sessions retrieved successfully',
  'Create training session': 'Training session created successfully',
  'Update training session': 'Training session updated successfully',
  'Delete training session': 'Training session deleted successfully',
  'Assign participants to session': 'Training assignments processed successfully',
  'Remove training assignment': 'Training assignment removed successfully',
  'Record check-in': 'Check-in recorded successfully',
  'List latest check-ins': 'Check-ins retrieved successfully',
  'Issue certificates': 'Certificates processed successfully',
  'List certificates': 'Certificates retrieved successfully',
  'Retry certificate delivery': 'Certificate delivery retried successfully',
  'Create CSV export': 'Export created successfully',
  'Get export status': 'Export retrieved successfully',
  'List audit events': 'Audit events retrieved successfully',
  'Update event settings': 'Event settings updated successfully',
  'List all FAQs': 'FAQs retrieved successfully',
  'Replace FAQs': 'FAQs updated successfully',
  'Provider delivery callback': 'Notification callback received successfully',
};

function url(path, query = []) {
  return {
    raw: `${base}/${path}`,
    host: [base],
    path: path.split('/').filter(Boolean),
    ...(query.length ? { query } : {}),
  };
}

function request(name, method, path, options = {}) {
  const {
    auth = null,
    body,
    headers = [],
    query = [],
    code = 200,
    status = 'OK',
    example,
    description = '',
    formdata,
    message,
    binary = false,
    binaryContentType = 'application/octet-stream',
    pagination,
    meta = [],
  } = options;

  const header = [...headers];
  if (body !== undefined) header.unshift(jsonHeader);

  const responseMessage = message ?? responseMessages[name] ?? `${name} completed successfully`;
  const responseBody = binary
    ? 'Binary file response (not JSON).'
    : JSON.stringify({
        status: true,
        message: responseMessage,
        data: example ?? null,
        ...(pagination ? { pagination, meta } : {}),
      }, null, 2);

  const item = {
    name,
    request: {
      method,
      header,
      url: url(path, query),
      ...(description ? { description } : {}),
      ...(auth ? { auth } : {}),
      ...(body !== undefined
        ? { body: { mode: 'raw', raw: JSON.stringify(body, null, 2), options: { raw: { language: 'json' } } } }
        : {}),
      ...(formdata
        ? { body: { mode: 'formdata', formdata } }
        : {}),
    },
    response: [
      {
        name: 'Example response',
        originalRequest: {
          method,
          header: header.map((h) => ({ ...h })),
          url: url(path, query),
        },
        status,
        code,
        _postman_previewlanguage: binary ? 'text' : 'json',
        header: binary
          ? [{ key: 'Content-Type', value: binaryContentType }]
          : [{ key: 'Content-Type', value: 'application/json' }, { key: 'X-Request-Id', value: 'req_example' }],
        cookie: [],
        body: responseBody,
      },
    ],
  };

  return item;
}

const bearer = (variable) => ({ type: 'bearer', bearer: [{ key: 'token', value: `{{${variable}}}`, type: 'string' }] });

const eventExample = {
  eventId: 'npis-2026',
  name: 'Nigeria Police Intelligence Summit 2026',
  startDate: '2026-12-08',
  endDate: '2026-12-10',
  timezone: 'Africa/Lagos',
  venue: { name: 'Police Resource Centre', address: 'Jabi, FCT – Abuja' },
  welcomeMessage: {
    title: 'Welcome to the Nigeria Police Intelligence Summit',
    author: 'IGP Kayode Adeolu Egbetokun, Ph.D, NPM',
    authorTitle: 'Inspector-General of Police',
    paragraphs: ['On behalf of the Nigeria Police Force, I welcome you to this summit on intelligence-led policing.'],
    approvedAt: '2026-11-01T09:00:00+01:00',
  },
  brochure: {
    title: 'NPIS 2026 Official e-Brochure',
    fileName: 'NPIS-2026-e-Brochure.pdf',
    url: 'https://npis.ng/storage/npis/brochures/NPIS-2026-e-Brochure.pdf',
    sizeBytes: 2480000,
    version: '1.2',
    publishedAt: '2026-11-05T10:00:00+01:00',
  },
  deadlines: {
    registrationClosesAt: '2026-12-07T23:59:00+01:00',
    travelEditDeadline: '2026-12-07T23:59:00+01:00',
    accommodationEditDeadline: '2026-12-07T23:59:00+01:00',
  },
  policies: {
    approvalRequired: true,
    certificateMinDaysAttended: 2,
    travelDatesFrom: '2026-11-01',
    accommodationCheckInFrom: '2026-11-01',
    accommodationCheckOutBy: '2026-12-13',
    verification: {
      codeLength: 5,
      codeTtlSeconds: 600,
      resendCooldownSeconds: 60,
      maxResends: 3,
      maxAttempts: 5,
    },
  },
};

const badgeExample = {
  category: {
    code: 'DL',
    name: 'Delegates',
    members: 'Registered delegates',
    color: '#0F7A4D',
    textColor: '#FFFFFF',
    photoRequired: false,
    reserved: false,
    zoneAccess: ['full', 'none', 'none', 'full', 'none', 'none', 'none'],
  },
  source: 'derived',
  photo: null,
  photoRequired: false,
  photoMissing: false,
};

const registrationExample = {
  registrationId: 'reg_01j9m2v8e4x6k3n5p7q8r9s0t1',
  summitTagNumber: 'NPIS26-K7Q4M',
  status: 'pending_approval',
  source: 'online',
  participantType: 'delegate',
  badge: badgeExample,
  contact: {
    phone: '+2348091234567',
    email: null,
    verifiedChannel: 'sms',
    verifiedAt: '2026-11-16T10:12:00+01:00',
  },
  profile: {
    fullName: 'Musa Abdullahi Bello',
    sector: 'police',
    rankCode: 'CSP',
    stateCode: 'KN',
    commandId: 'cmd_kn',
    unitId: 'cmd_kn_sid',
    unitOtherName: null,
    organization: null,
    designation: null,
    vendorService: null,
    vendorServiceOther: null,
  },
  itinerary: {
    travelMode: 'air',
    arrival: { date: '2026-12-07', time: null, timeWindow: 'afternoon', planStatus: 'preferred' },
    departure: { date: '2026-12-10', time: null, timeWindow: 'evening', planStatus: 'preferred' },
    postSummitPlan: 'leave_immediately',
    flight: {
      departureAirportCode: 'KAN',
      flightTime: null,
      flightTimeWindow: 'morning',
      flightNumber: null,
    },
    timezone: 'Africa/Lagos',
  },
  accommodation: null,
  interests: { track: 'both', transportSubsectors: ['road', 'air'] },
  reviewFlags: [],
  duplicateOfRegistrationId: null,
  trainingAssignments: [],
  attendance: { checkedInDates: [] },
  certificate: { eligibility: 'not_eligible', certificateId: null },
  submittedAt: '2026-11-16T10:15:00+01:00',
  updatedAt: '2026-11-16T10:15:00+01:00',
  version: 1,
};

const listPagination = { page: 1, pageSize: 25, totalItems: 1, totalPages: 1 };
const listExample = {
  data: [registrationExample],
  pagination: listPagination,
};

const travelReport = {
  total: 177,
  byMode: [
    { key: 'air', label: 'Air', count: 120 },
    { key: 'road', label: 'Road', count: 42 },
    { key: 'rail', label: 'Rail', count: 5 },
    { key: 'sea', label: 'Sea / waterway', count: 3 },
    { key: 'undecided', label: 'Undecided', count: 7 },
  ],
  arrivalsByDate: [
    { key: '2026-12-07', label: 'Mon, 7 Dec 2026', count: 96 },
    { key: '2026-12-08', label: 'Tue, 8 Dec 2026', count: 54 },
    { key: 'undecided', label: 'Undecided', count: 27 },
  ],
  arrivalsByWindow: [
    { key: 'early_morning', label: 'Early morning (05:00–08:59)', count: 18 },
    { key: 'morning', label: 'Morning (09:00–11:59)', count: 46 },
    { key: 'afternoon', label: 'Afternoon (12:00–15:59)', count: 62 },
    { key: 'evening', label: 'Evening (16:00–19:59)', count: 38 },
    { key: 'night', label: 'Night (20:00–04:59)', count: 13 },
    { key: 'undecided', label: 'Undecided', count: 0 },
  ],
  departuresByDate: [{ key: '2026-12-10', label: 'Thu, 10 Dec 2026', count: 101 }],
  departuresByWindow: [{ key: 'morning', label: 'Morning (09:00–11:59)', count: 74 }],
  byAirport: [
    { key: 'KAN', label: 'Kano (KAN)', count: 41 },
    { key: 'LOS', label: 'Lagos (LOS)', count: 63 },
    { key: 'undecided', label: 'Undecided', count: 5 },
  ],
  byPostSummitPlan: [
    { key: 'leave_immediately', label: 'Leave immediately after the summit', count: 150 },
    { key: 'extend_stay', label: 'Extend stay in Abuja', count: 20 },
    { key: 'undecided', label: 'Undecided', count: 7 },
  ],
  pendingPlans: 7,
};

const folder = (name, items, description = '') => ({ name, description, item: items });

const collection = {
  info: {
    _postman_id: '7d1a5f62-6c1b-4d0b-9e67-npis2026api',
    name: 'NPIS Registration & Organizer Management API',
    description:
      'Nigeria Police Intelligence Summit (NPIS) API v1. Import against {{baseUrl}} = http://localhost:8000/api/v1. Every JSON success response uses the shared HttpResponse envelope {status, message, data}. Participant photos, certificates, exports and brochures are stored in Cloudinary with ImageKit fallback; signed media URLs proxy those cloud objects. Errors use {status:false, message, data:{code, details, requestId}}. Participant endpoints require a two-hour session from verification. Organizer endpoints require an access grant then an OIDC organizer session. Idempotent operations require Idempotency-Key; itinerary and accommodation updates require If-Match.',
    schema: 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
  },
  variable: [
    { key: 'baseUrl', value: 'http://localhost:8000/api/v1' },
    { key: 'participantToken', value: '' },
    { key: 'adminToken', value: '' },
    { key: 'accessToken', value: '' },
    { key: 'verificationId', value: '' },
    { key: 'registrationId', value: '' },
    { key: 'summitTagNumber', value: '' },
    { key: 'sessionId', value: 'ses_road_1' },
    { key: 'assignmentId', value: '' },
    { key: 'certificateId', value: '' },
    { key: 'exportId', value: '' },
    { key: 'ifMatch', value: '1' },
    { key: 'idempotencyKey', value: '{{$guid}}' },
  ],
  item: [
    folder('Public & Reference Data', [
      request('Get event', 'GET', 'event', { example: eventExample, description: 'Cached for at most five minutes.' }),
      request('Get published FAQs', 'GET', 'event/faqs', {
        example: [
          {
            faqId: 'faq_01',
            question: 'Who should register for the summit?',
            answer: 'Delegates and vendors. See the full answer on the public site.',
            category: 'registration',
            order: 1,
            published: true,
            updatedAt: '2026-11-05T10:00:00+01:00',
          },
        ],
      }),
      request('Get ranks', 'GET', 'reference/ranks', {
        example: [{ code: 'CSP', name: 'Chief Superintendent of Police', abbreviation: 'CSP', category: 'senior_officer' }],
      }),
      request('Get states', 'GET', 'reference/states', {
        example: [{ code: 'FC', name: 'Federal Capital Territory' }, { code: 'KN', name: 'Kano' }],
      }),
      request('Get commands', 'GET', 'reference/commands', {
        query: [{ key: 'stateCode', value: 'KN' }],
        example: [{ commandId: 'cmd_kn', stateCode: 'KN', name: 'Kano State Police Command', type: 'state_command' }],
      }),
      request('Get units', 'GET', 'reference/units', {
        query: [{ key: 'commandId', value: 'cmd_kn' }],
        example: [{ unitId: 'cmd_kn_sid', commandId: 'cmd_kn', name: 'State Intelligence Department', type: 'department' }],
      }),
      request('Get airports', 'GET', 'reference/airports', {
        example: [{ code: 'KAN', name: 'Mallam Aminu Kano International Airport', city: 'Kano', stateCode: 'KN' }],
      }),
      request('Get banks', 'GET', 'reference/banks', {
        example: [{ code: '057', name: 'Zenith Bank' }],
      }),
      request('Get protocol', 'GET', 'reference/protocol', {
        example: {
          status: 'proposed',
          zones: [{ code: 'Z1', name: 'Main hall' }],
          categories: [
            {
              code: 'DL',
              name: 'Delegates',
              members: 'Registered delegates',
              color: '#0F7A4D',
              textColor: '#FFFFFF',
              photoRequired: false,
              reserved: false,
              zoneAccess: ['full', 'none', 'none', 'full', 'none', 'none', 'none'],
            },
          ],
        },
      }),
    ]),

    folder('Authentication & Verification', [
      request('Initiate OTP verification', 'POST', 'verifications', {
        body: { channel: 'sms', destination: '+2348091234567', purpose: 'registration' },
        code: 201,
        status: 'Created',
        example: {
          verificationId: 'ver_k7q4m2x8n1p5',
          channel: 'sms',
          purpose: 'registration',
          maskedDestination: '+234 809 *** 4567',
          codeLength: 5,
          expiresAt: '2026-11-16T10:12:00+01:00',
          resendAvailableAt: '2026-11-16T10:11:00+01:00',
          resendsRemaining: 3,
          attemptsRemaining: 5,
          deliveryStatus: 'queued',
        },
      }),
      request('Get verification status', 'GET', 'verifications/{{verificationId}}', {
        example: {
          verificationId: '{{verificationId}}',
          channel: 'sms',
          purpose: 'registration',
          maskedDestination: '+234 809 *** 4567',
          codeLength: 5,
          expiresAt: '2026-11-16T10:12:00+01:00',
          resendAvailableAt: '2026-11-16T10:11:00+01:00',
          resendsRemaining: 2,
          attemptsRemaining: 5,
          deliveryStatus: 'sent',
        },
      }),
      request('Resend verification code', 'POST', 'verifications/{{verificationId}}/resend', {
        example: {
          verificationId: '{{verificationId}}',
          channel: 'sms',
          purpose: 'registration',
          maskedDestination: '+234 809 *** 4567',
          codeLength: 5,
          expiresAt: '2026-11-16T10:16:00+01:00',
          resendAvailableAt: '2026-11-16T10:12:00+01:00',
          resendsRemaining: 1,
          attemptsRemaining: 3,
          deliveryStatus: 'queued',
        },
      }),
      request('Confirm code and issue participant session', 'POST', 'verifications/{{verificationId}}/confirm', {
        body: { code: '12345' },
        example: {
          sessionToken: 'participant-session-token',
          sessionExpiresAt: '2026-11-16T12:12:00+01:00',
          contact: { channel: 'sms', destination: '+2348091234567', verifiedAt: '2026-11-16T10:12:00+01:00' },
          existingRegistration: null,
        },
      }),
    ]),

    folder('Participant Registration', [
      request('Submit registration', 'POST', 'registrations', {
        auth: bearer('participantToken'),
        headers: [idemHeader],
        code: 201,
        status: 'Created',
        body: {
          participantType: 'delegate',
          profile: {
            fullName: 'Musa Abdullahi Bello',
            sector: 'police',
            rankCode: 'CSP',
            stateCode: 'KN',
            commandId: 'cmd_kn',
            unitId: 'cmd_kn_sid',
            unitOtherName: null,
            organization: null,
            designation: null,
            vendorService: null,
            vendorServiceOther: null,
          },
          itinerary: registrationExample.itinerary,
          accommodation: null,
          interests: { track: 'both', transportSubsectors: ['road', 'air'] },
          photo: null,
          consent: { dataProcessing: true },
        },
        example: registrationExample,
      }),
      request('Get my registration', 'GET', 'registrations/me', {
        auth: bearer('participantToken'),
        example: registrationExample,
      }),
      request('Upload or replace my photo', 'PUT', 'registrations/me/photo', {
        auth: bearer('participantToken'),
        body: { photo: 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD...' },
        example: {
          ...registrationExample,
          badge: {
            ...badgeExample,
            photo: { url: 'https://api.example.test/api/v1/media/photos/reg_x?expires=...&signature=...', source: 'registrant', uploadedAt: '2026-11-16T10:20:00+01:00' },
          },
        },
      }),
      request('Update itinerary', 'PUT', 'registrations/me/itinerary', {
        auth: bearer('participantToken'),
        headers: [ifMatchHeader],
        body: registrationExample.itinerary,
        example: { ...registrationExample, version: 2 },
      }),
      request('Update accommodation', 'PUT', 'registrations/me/accommodation', {
        auth: bearer('participantToken'),
        headers: [ifMatchHeader],
        body: {
          preference: 'organizer_arranged',
          checkInDate: '2026-12-07',
          checkOutDate: '2026-12-11',
          specialNeeds: 'Ground-floor room',
          allowanceRequested: false,
          payoutAccount: null,
        },
        example: {
          ...registrationExample,
          participantType: 'delegate',
          accommodation: {
            preference: 'organizer_arranged',
            checkInDate: '2026-12-07',
            checkOutDate: '2026-12-11',
            specialNeeds: 'Ground-floor room',
            allowanceRequested: false,
            payoutAccount: null,
            allocationStatus: 'requested',
            allocation: null,
          },
          version: 2,
        },
      }),
      request('Get my schedule', 'GET', 'registrations/me/schedule', {
        auth: bearer('participantToken'),
        example: [
          {
            sessionId: 'ses_road_1',
            title: 'Road Transport Intelligence & the E-Manifest System',
            track: 'transportation',
            subsector: 'road',
            date: '2026-12-08',
            startTime: '14:00',
            endTime: '16:00',
            room: 'Hall A',
            capacity: 60,
            assignedCount: 41,
          },
        ],
      }),
      request('Get my certificate', 'GET', 'registrations/me/certificate', {
        auth: bearer('participantToken'),
        example: {
          certificateNumber: 'NPIS26-CERT-00001',
          downloadUrl: 'https://api.example.test/api/v1/media/certificates/cer_x?expires=...&signature=...',
          expiresAt: '2026-11-16T10:35:00+01:00',
        },
      }),
    ]),

    folder('Organizer Authentication', [
      request('Exchange organizer access code', 'POST', 'admin/access', {
        body: { code: 'NPIS-ORG-2026' },
        code: 201,
        status: 'Created',
        example: { accessToken: 'organizer-access-grant', expiresAt: '2026-11-16T18:12:00+01:00' },
      }),
      request('Exchange OIDC token for organizer session', 'POST', 'admin/session', {
        auth: bearer('accessToken'),
        body: { idToken: 'eyJhbGciOiJSUzI1NiIsImtpZCI6...' },
        code: 201,
        status: 'Created',
        description: 'The mock role-picker is intentionally not implemented. The idToken must be a valid OIDC identity token with a role claim.',
        example: {
          token: 'organizer-session-token',
          user: { userId: 'usr_1', name: 'ACP Grace Adeleke', role: 'super_admin' },
          permissions: [
            'registrations:read',
            'registrations:review',
            'contact:read',
            'travel:read',
            'accommodation:read',
            'accommodation:allocate',
            'training:read',
            'training:assign',
            'attendance:read',
            'attendance:record',
            'certificates:manage',
            'exports:create',
            'audit:read',
            'event:manage',
          ],
        },
      }),
      request('Change organizer access code', 'PUT', 'admin/access-code', {
        auth: bearer('adminToken'),
        body: { code: 'NEW-SECURE-CODE-2026' },
        code: 204,
        status: 'No Content',
        example: {},
      }),
    ]),

    folder('Organizer Registration Management', [
      request('List registrations', 'GET', 'admin/registrations', {
        auth: bearer('adminToken'),
        query: [
          { key: 'page', value: '1' },
          { key: 'pageSize', value: '25' },
          { key: 'status', value: 'pending_approval' },
          { key: 'sort', value: '-submittedAt' },
        ],
        example: listExample,
      }),
      request('Get registration', 'GET', 'admin/registrations/{{registrationId}}', {
        auth: bearer('adminToken'),
        example: registrationExample,
      }),
      request('Review registration', 'POST', 'admin/registrations/{{registrationId}}/review', {
        auth: bearer('adminToken'),
        body: { action: 'approve' },
        example: { ...registrationExample, status: 'approved', version: 2 },
      }),
      request('Assign badge category', 'PUT', 'admin/registrations/{{registrationId}}/badge', {
        auth: bearer('adminToken'),
        body: { category: 'VP', note: 'Protocol list' },
        example: {
          ...registrationExample,
          badge: { ...badgeExample, category: { ...badgeExample.category, code: 'VP', name: 'VIP & Special Guests' }, source: 'assigned' },
        },
      }),
      request('Set or remove registration photo', 'PUT', 'admin/registrations/{{registrationId}}/photo', {
        auth: bearer('adminToken'),
        body: { photo: null },
        example: { ...registrationExample, badge: { ...badgeExample, photo: null } },
      }),
      request('Allocate accommodation', 'PUT', 'admin/registrations/{{registrationId}}/accommodation-allocation', {
        auth: bearer('adminToken'),
        body: { allocationStatus: 'allocated', hotelName: 'Jabi Lake Partner Hotel', roomType: 'Executive' },
        example: {
          ...registrationExample,
          accommodation: {
            preference: 'organizer_arranged',
            checkInDate: '2026-12-07',
            checkOutDate: '2026-12-11',
            specialNeeds: null,
            allowanceRequested: false,
            payoutAccount: null,
            allocationStatus: 'allocated',
            allocation: { hotelName: 'Jabi Lake Partner Hotel', roomType: 'Executive', confirmedAt: '2026-11-16T10:30:00+01:00' },
          },
          version: 2,
        },
      }),
    ]),

    folder('Reports', [
      request('Registration report', 'GET', 'admin/reports/registration', {
        auth: bearer('adminToken'),
        example: {
          total: 221,
          byStatus: [
            { key: 'incomplete', label: 'Incomplete', count: 12 },
            { key: 'pending_approval', label: 'Pending approval', count: 34 },
            { key: 'approved', label: 'Approved', count: 168 },
            { key: 'rejected', label: 'Rejected', count: 5 },
            { key: 'cancelled', label: 'Cancelled', count: 2 },
          ],
          byParticipantType: [
            { key: 'delegate', label: 'Delegate', count: 191 },
            { key: 'vendor', label: 'Vendor', count: 30 },
          ],
          bySector: [{ key: 'police', label: 'Nigeria Police Force', count: 177 }],
          byVendorService: [{ key: 'photographer', label: 'Photographer', count: 3 }],
          byProtocolCategory: [{ key: 'DL', label: 'DL · Delegates', count: 191 }],
          photosMissing: 24,
          byState: [{ key: 'KN', label: 'Kano', count: 10 }],
          byCommand: [{ key: 'cmd_kn', label: 'Kano State Police Command', count: 10 }],
          byUnit: [{ key: 'cmd_kn_sid', label: 'State Intelligence Department', count: 4 }],
          reviewQueue: [
            { key: 'possible_duplicate', label: 'Possible duplicate', count: 2 },
            { key: 'unlisted_unit', label: 'Unlisted unit', count: 8 },
          ],
        },
      }),
      request('Travel report', 'GET', 'admin/reports/travel', { auth: bearer('adminToken'), example: travelReport }),
      request('Accommodation report', 'GET', 'admin/reports/accommodation', {
        auth: bearer('adminToken'),
        example: {
          total: 64,
          byPreference: [
            { key: 'organizer_arranged', label: 'Organizer-arranged', count: 38 },
            { key: 'self_arranged', label: 'Self-arranged', count: 26 },
          ],
          byAllocationStatus: [
            { key: 'requested', label: 'Requested — awaiting allocation', count: 20 },
            { key: 'allocated', label: 'Allocated', count: 15 },
            { key: 'waitlisted', label: 'Waitlisted', count: 2 },
            { key: 'declined', label: 'Declined', count: 1 },
          ],
          allowanceRequested: 26,
          checkInsByDate: [{ key: '2026-12-07', label: 'Mon, 7 Dec 2026', count: 31 }],
          checkOutsByDate: [{ key: '2026-12-11', label: 'Fri, 11 Dec 2026', count: 22 }],
          roomNightsByNight: [{ key: '2026-12-07', label: 'Night of Mon, 7 Dec 2026', count: 30 }],
        },
      }),
      request('Training report', 'GET', 'admin/reports/training', {
        auth: bearer('adminToken'),
        example: {
          total: 191,
          byTrack: [
            { key: 'transportation', label: 'Transportation', count: 81 },
            { key: 'hospitality', label: 'Hospitality', count: 47 },
            { key: 'both', label: 'Both', count: 63 },
          ],
          bySubsector: [
            { key: 'road', label: 'Road', count: 102 },
            { key: 'air', label: 'Air', count: 54 },
            { key: 'sea', label: 'Sea', count: 12 },
            { key: 'rail', label: 'Rail', count: 9 },
          ],
          unassigned: 78,
          sessions: [
            {
              sessionId: 'ses_road_1',
              title: 'Road Transport Intelligence & the E-Manifest System',
              track: 'transportation',
              subsector: 'road',
              date: '2026-12-08',
              startTime: '14:00',
              endTime: '16:00',
              room: 'Hall A',
              capacity: 60,
              assignedCount: 41,
            },
          ],
        },
      }),
      request('Attendance report', 'GET', 'admin/reports/attendance', {
        auth: bearer('adminToken'),
        example: {
          total: 221,
          checkInsByDate: [
            { key: '2026-12-08', label: 'Tue, 8 Dec 2026', count: 160 },
            { key: '2026-12-09', label: 'Wed, 9 Dec 2026', count: 148 },
            { key: '2026-12-10', label: 'Thu, 10 Dec 2026', count: 132 },
          ],
          byEligibility: [
            { key: 'eligible', label: 'Eligible', count: 44 },
            { key: 'issued', label: 'Issued', count: 120 },
            { key: 'not_eligible', label: 'Not eligible', count: 57 },
          ],
          byDeliveryStatus: [
            { key: 'queued', label: 'Queued', count: 3 },
            { key: 'sent', label: 'Sent', count: 2 },
            { key: 'delivered', label: 'Delivered', count: 113 },
            { key: 'failed', label: 'Failed', count: 2 },
          ],
        },
      }),
    ]),

    folder('Training Management', [
      request('List training sessions', 'GET', 'admin/training/sessions', {
        auth: bearer('adminToken'),
        example: [
          {
            sessionId: 'ses_road_1',
            title: 'Road Transport Intelligence & the E-Manifest System',
            track: 'transportation',
            subsector: 'road',
            date: '2026-12-08',
            startTime: '14:00',
            endTime: '16:00',
            room: 'Hall A',
            capacity: 60,
            assignedCount: 41,
          },
        ],
      }),
      request('Create training session', 'POST', 'admin/training/sessions', {
        auth: bearer('adminToken'),
        code: 201,
        status: 'Created',
        body: {
          title: 'Road Transport Intelligence & the E-Manifest System',
          track: 'transportation',
          subsector: 'road',
          date: '2026-12-08',
          startTime: '14:00',
          endTime: '16:00',
          room: 'Hall A',
          capacity: 60,
        },
        example: {
          sessionId: 'ses_new',
          title: 'Road Transport Intelligence & the E-Manifest System',
          track: 'transportation',
          subsector: 'road',
          date: '2026-12-08',
          startTime: '14:00',
          endTime: '16:00',
          room: 'Hall A',
          capacity: 60,
          assignedCount: 0,
        },
      }),
      request('Update training session', 'PUT', 'admin/training/sessions/{{sessionId}}', {
        auth: bearer('adminToken'),
        body: {
          title: 'Road Transport Intelligence & the E-Manifest System',
          track: 'transportation',
          subsector: 'road',
          date: '2026-12-08',
          startTime: '14:00',
          endTime: '16:00',
          room: 'Hall A',
          capacity: 70,
        },
        example: {
          sessionId: '{{sessionId}}',
          title: 'Road Transport Intelligence & the E-Manifest System',
          track: 'transportation',
          subsector: 'road',
          date: '2026-12-08',
          startTime: '14:00',
          endTime: '16:00',
          room: 'Hall A',
          capacity: 70,
          assignedCount: 41,
        },
      }),
      request('Delete training session', 'DELETE', 'admin/training/sessions/{{sessionId}}', {
        auth: bearer('adminToken'),
        code: 204,
        status: 'No Content',
        example: {},
      }),
      request('Assign participants to session', 'POST', 'admin/training/sessions/{{sessionId}}/assignments', {
        auth: bearer('adminToken'),
        body: { registrationIds: ['{{registrationId}}'] },
        example: {
          results: [
            {
              registrationId: '{{registrationId}}',
              outcome: 'assigned',
              assignment: {
                assignmentId: 'asg_01j9m3',
                registrationId: '{{registrationId}}',
                sessionId: 'ses_road_1',
                assignedAt: '2026-11-16T10:40:00+01:00',
                assignedBy: 'ACP Grace Adeleke',
              },
            },
          ],
          session: { sessionId: 'ses_road_1', assignedCount: 42 },
        },
      }),
      request('Remove training assignment', 'DELETE', 'admin/training/assignments/{{assignmentId}}', {
        auth: bearer('adminToken'),
        code: 204,
        status: 'No Content',
        example: {},
      }),
    ]),

    folder('Attendance & Check-in', [
      request('Record check-in', 'POST', 'admin/check-ins', {
        auth: bearer('adminToken'),
        headers: [idemHeader],
        code: 201,
        status: 'Created',
        body: { summitTagNumber: 'NPIS26-K7Q4M', eventDate: '2026-12-08', method: 'qr' },
        example: {
          checkIn: {
            checkInId: 'chk_01j9m4',
            registrationId: '{{registrationId}}',
            summitTagNumber: 'NPIS26-K7Q4M',
            eventDate: '2026-12-08',
            checkedInAt: '2026-12-08T08:42:00+01:00',
            method: 'qr',
            recordedBy: 'ACP Grace Adeleke',
          },
          officer: {
            participantType: 'delegate',
            fullName: 'Musa Abdullahi Bello',
            sector: 'police',
            rankCode: 'CSP',
            commandId: 'cmd_kn',
            organization: null,
            vendorService: null,
            vendorServiceOther: null,
            status: 'approved',
            badge: badgeExample,
            zones: ['full', 'none', 'none', 'full', 'none', 'none', 'none'],
            missingPhotoWarning: false,
          },
          replayed: false,
        },
      }),
      request('List latest check-ins', 'GET', 'admin/check-ins', {
        auth: bearer('adminToken'),
        query: [{ key: 'eventDate', value: '2026-12-08' }],
        example: [
          {
            checkInId: 'chk_01j9m4',
            registrationId: '{{registrationId}}',
            summitTagNumber: 'NPIS26-K7Q4M',
            eventDate: '2026-12-08',
            checkedInAt: '2026-12-08T08:42:00+01:00',
            method: 'qr',
            recordedBy: 'ACP Grace Adeleke',
          },
        ],
      }),
    ]),

    folder('Certificates', [
      request('Issue certificates', 'POST', 'admin/certificates', {
        auth: bearer('adminToken'),
        headers: [idemHeader],
        code: 201,
        status: 'Created',
        body: { registrationIds: ['{{registrationId}}'] },
        example: {
          results: [
            {
              registrationId: '{{registrationId}}',
              outcome: 'issued',
              certificate: {
                certificateId: 'cer_01j9m5',
                certificateNumber: 'NPIS26-CERT-00001',
                registrationId: '{{registrationId}}',
                summitTagNumber: 'NPIS26-K7Q4M',
                issuedAt: '2026-11-16T10:50:00+01:00',
                issuedBy: 'ACP Grace Adeleke',
                delivery: { channel: 'sms', status: 'queued', attempts: 0, lastAttemptAt: null, lastError: null },
              },
            },
          ],
        },
      }),
      request('List certificates', 'GET', 'admin/certificates', {
        auth: bearer('adminToken'),
        query: [{ key: 'deliveryStatus', value: 'failed' }],
        example: [
          {
            certificateId: 'cer_01j9m5',
            certificateNumber: 'NPIS26-CERT-00001',
            registrationId: '{{registrationId}}',
            summitTagNumber: 'NPIS26-K7Q4M',
            issuedAt: '2026-11-16T10:50:00+01:00',
            issuedBy: 'ACP Grace Adeleke',
            delivery: { channel: 'sms', status: 'failed', attempts: 2, lastAttemptAt: '2026-11-16T10:55:00+01:00', lastError: 'Carrier rejected message' },
          },
        ],
      }),
      request('Retry certificate delivery', 'POST', 'admin/certificates/{{certificateId}}/deliveries', {
        auth: bearer('adminToken'),
        headers: [idemHeader],
        code: 202,
        status: 'Accepted',
        example: {
          certificateId: '{{certificateId}}',
          certificateNumber: 'NPIS26-CERT-00001',
          registrationId: '{{registrationId}}',
          summitTagNumber: 'NPIS26-K7Q4M',
          issuedAt: '2026-11-16T10:50:00+01:00',
          issuedBy: 'ACP Grace Adeleke',
          delivery: { channel: 'sms', status: 'queued', attempts: 2, lastAttemptAt: '2026-11-16T10:55:00+01:00', lastError: null },
        },
      }),
    ]),

    folder('Exports', [
      request('Create CSV export', 'POST', 'admin/exports', {
        auth: bearer('adminToken'),
        headers: [idemHeader],
        code: 202,
        status: 'Accepted',
        body: {
          filters: { status: 'approved', participantType: 'delegate' },
          fields: ['summitTagNumber', 'fullName', 'organization', 'status'],
          format: 'csv',
        },
        example: {
          exportId: 'exp_01j9m6',
          status: 'queued',
          format: 'csv',
          rowCount: 168,
          filters: { status: 'approved', participantType: 'delegate' },
          fields: ['summitTagNumber', 'fullName', 'organization', 'status'],
          downloadUrl: null,
          expiresAt: '2026-11-16T11:05:00+01:00',
          createdAt: '2026-11-16T10:50:00+01:00',
        },
      }),
      request('Get export status', 'GET', 'admin/exports/{{exportId}}', {
        auth: bearer('adminToken'),
        example: {
          exportId: '{{exportId}}',
          status: 'ready',
          format: 'csv',
          rowCount: 168,
          filters: { status: 'approved', participantType: 'delegate' },
          fields: ['summitTagNumber', 'fullName', 'organization', 'status'],
          downloadUrl: 'https://api.example.test/api/v1/media/exports/{{exportId}}?expires=...&signature=...',
          expiresAt: '2026-11-16T11:05:00+01:00',
          createdAt: '2026-11-16T10:50:00+01:00',
        },
      }),
    ]),

    folder('Audit', [
      request('List audit events', 'GET', 'admin/audit-events', {
        auth: bearer('adminToken'),
        query: [
          { key: 'page', value: '1' },
          { key: 'pageSize', value: '25' },
          { key: 'action', value: 'registration.review' },
        ],
        example: {
          data: [
            {
              auditId: 'aud_01j9m7',
              occurredAt: '2026-11-16T10:55:00+01:00',
              actor: { userId: '1', name: 'ACP Grace Adeleke', role: 'super_admin' },
              action: 'registration.review.approve',
              targetType: 'registration',
              targetId: '{{registrationId}}',
              summary: 'approve — Musa Abdullahi Bello (NPIS26-K7Q4M)',
              metadata: { before: 'pending_approval', after: 'approved', note: null },
            },
          ],
          pagination: { page: 1, pageSize: 25, totalItems: 1, totalPages: 1 },
        },
      }),
    ]),

    folder('Event & FAQ Management', [
      request('Update event settings', 'PUT', 'admin/event', {
        auth: bearer('adminToken'),
        body: {
          deadlines: { travelEditDeadline: '2026-12-06T23:59:00+01:00' },
          policies: { approvalRequired: true, verification: { codeLength: 6 } },
          welcomeMessage: { title: 'Welcome to NPIS 2026' },
        },
        example: eventExample,
      }),
      request('List all FAQs', 'GET', 'admin/faqs', {
        auth: bearer('adminToken'),
        example: [
          {
            faqId: 'faq_01',
            question: 'Who should register for the summit?',
            answer: 'Delegates and vendors.',
            category: 'registration',
            order: 1,
            published: true,
            updatedAt: '2026-11-05T10:00:00+01:00',
          },
        ],
      }),
      request('Replace FAQs', 'PUT', 'admin/faqs', {
        auth: bearer('adminToken'),
        body: {
          items: [
            { faqId: 'faq_01', question: 'Who should register for the summit?', answer: 'Delegates and vendors.', category: 'registration', published: true },
            { question: 'When does registration close?', answer: 'Registration closes on 7 December 2026.', category: 'registration', published: true },
          ],
        },
        example: [
          { faqId: 'faq_01', question: 'Who should register for the summit?', answer: 'Delegates and vendors.', category: 'registration', order: 1, published: true, updatedAt: '2026-11-16T10:55:00+01:00' },
          { faqId: 'faq_02', question: 'When does registration close?', answer: 'Registration closes on 7 December 2026.', category: 'registration', order: 2, published: true, updatedAt: '2026-11-16T10:55:00+01:00' },
        ],
      }),
    ]),

    folder('Notification Callbacks', [
      request('Provider delivery callback', 'POST', 'webhooks/notifications/termii', {
        headers: [{ key: 'X-NPIS-Signature', value: '{{notificationSignature}}' }],
        body: { messageId: 'msg_01j9m8', status: 'delivered' },
        example: { received: true },
      }),
    ]),

    folder('Signed Media', [
      request('Download photo (signed URL)', 'GET', 'media/photos/{{registrationId}}', {
        query: [
          { key: 'expires', value: '{{mediaExpires}}' },
          { key: 'signature', value: '{{mediaSignature}}' },
        ],
        description: 'Cloudinary/ImageKit-backed URLs are returned in the badge.photo.url field and expire after 15 minutes.',
        example: {},
        binary: true,
        binaryContentType: 'image/jpeg',
      }),
      request('Download certificate (signed URL)', 'GET', 'media/certificates/{{certificateId}}', {
        query: [
          { key: 'expires', value: '{{mediaExpires}}' },
          { key: 'signature', value: '{{mediaSignature}}' },
        ],
        description: 'Cloudinary/ImageKit-backed URLs are returned by GET /registrations/me/certificate and expire after 15 minutes.',
        example: {},
        binary: true,
        binaryContentType: 'application/pdf',
      }),
      request('Download export (signed URL)', 'GET', 'media/exports/{{exportId}}', {
        query: [
          { key: 'expires', value: '{{mediaExpires}}' },
          { key: 'signature', value: '{{mediaSignature}}' },
        ],
        description: 'Cloudinary/ImageKit-backed URLs are returned by GET /admin/exports/{exportId} and expire after 15 minutes.',
        example: {},
        binary: true,
        binaryContentType: 'text/csv',
      }),
    ]),
  ],
};

fs.mkdirSync('postman', { recursive: true });
fs.writeFileSync('postman/NPIS-API.postman_collection.json', `${JSON.stringify(collection, null, 2)}\n`);
console.log(`Wrote postman/NPIS-API.postman_collection.json with ${collection.item.reduce((sum, f) => sum + f.item.length, 0)} requests.`);
