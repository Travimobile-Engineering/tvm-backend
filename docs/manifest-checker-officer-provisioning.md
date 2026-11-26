# Manifest Checker — Officer Provisioning API

Base URL:

```text
/api/v1/manifest-checker
```

All responses use the standard envelope:

```json
{
  "status": true,
  "message": "Message",
  "data": {}
}
```

Officer provisioning endpoints require the `officers:manage` permission
(`state_commander` and `admin` roles).

---

## Account lifecycle

```text
Created with invite  -> pending_activation -> activation -> active/off_duty
Created with temp    -> off_duty + must_change_password -> login -> change password
Admin reset          -> off_duty + temporary password -> login -> change password
```

There is no public self-registration.

---

## 1. Create officer account

```http
POST /officers
Authorization: Bearer <admin-token>
Content-Type: application/json
```

### Request

```json
{
  "serviceNumber": "NPF-7001",
  "rank": "Inspector",
  "fullName": "New Field Officer",
  "email": "new.officer@npf.gov.ng",
  "phone": "+2348011111111",
  "unit": "Manifest Enforcement",
  "stateCode": "LA",
  "lgaId": "lga_la_1",
  "dutyPointId": "dp_lekki",
  "role": "duty_officer",
  "nin": "12345678901",
  "division": "Manifest Enforcement",
  "reportsTo": "CSP Ada Nwosu",
  "opsRoom": "+234 803 000 0099",
  "language": "en",
  "sendInvite": true
}
```

### Required fields

| Field | Type | Notes |
|---|---|---|
| `serviceNumber` | string | Unique |
| `rank` | string | |
| `fullName` | string | |
| `email` | string | Unique |
| `phone` | string | |
| `stateCode` | string | Must exist in `emf_states` |
| `role` | string | One of configured `emf.roles` |

### Invite mode — `sendInvite: true` (default)

```json
{
  "status": true,
  "message": "Officer account created.",
  "data": {
    "officer": {
      "officerId": "off_new_001",
      "serviceNumber": "NPF-7001",
      "fullName": "New Field Officer",
      "role": "duty_officer",
      "status": "pending_activation",
      "mustChangePassword": false,
      "activatedAt": null
    },
    "invitation": {
      "activationToken": "c9f0a13e...",
      "expiresAt": "2026-11-28T07:00:00+01:00",
      "delivery": "manual",
      "note": "Send this token to the officer through a secure channel."
    },
    "temporaryPassword": null,
    "temporaryPasswordExpiresAt": null,
    "mustChangePassword": false
  }
}
```

The activation token is valid for **48 hours**. Send it to the officer through a secure channel.

### Temporary-password mode — `sendInvite: false`

Returns a one-time temporary password valid for **24 hours**:

```json
{
  "status": true,
  "message": "Officer account created.",
  "data": {
    "officer": {
      "officerId": "off_new_002",
      "serviceNumber": "NPF-7002",
      "status": "off_duty",
      "mustChangePassword": true
    },
    "invitation": null,
    "temporaryPassword": "Ntem-AB12-3456",
    "temporaryPasswordExpiresAt": "2026-11-27T07:00:00+01:00",
    "mustChangePassword": true
  }
}
```

---

## 2. Resend activation invite

```http
POST /officers/{officerId}/invite
Authorization: Bearer <admin-token>
```

Regenerates the activation token for a `pending_activation` officer.

### Response

```json
{
  "status": true,
  "message": "Activation invite resent.",
  "data": {
    "officer": {
      "officerId": "off_new_001",
      "serviceNumber": "NPF-7001",
      "status": "pending_activation"
    },
    "invitation": {
      "activationToken": "new-token-8f2c...",
      "expiresAt": "2026-11-28T07:10:00+01:00",
      "delivery": "manual"
    },
    "temporaryPassword": null,
    "mustChangePassword": false
  }
}
```

### Errors

| Status | Code | Meaning |
|---|---|---|
| `409` | `ALREADY_ACTIVATED` | Account is already active; use password reset |

---

## 3. Reset officer password

```http
POST /officers/{officerId}/reset-password
Authorization: Bearer <admin-token>
```

Generates a temporary password, revokes all existing sessions, and requires a password change after login.

### Response

```json
{
  "status": true,
  "message": "Temporary password generated.",
  "data": {
    "officer": {
      "officerId": "off_new_001",
      "serviceNumber": "NPF-7001",
      "status": "off_duty",
      "mustChangePassword": true
    },
    "temporaryPassword": "Ntem-XY34-6789",
    "expiresAt": "2026-11-27T07:10:00+01:00",
    "mustChangePassword": true
  }
}
```

The temporary password expires after **24 hours**.

---

## 4. Activate officer account

Public endpoint — no bearer token required.

```http
POST /auth/activate
Content-Type: application/json
```

### Request

```json
{
  "serviceNumber": "NPF-7001",
  "token": "activation-token-from-invite",
  "password": "Activated@123",
  "password_confirmation": "Activated@123"
}
```

### Response

```json
{
  "status": true,
  "message": "Officer account activated.",
  "data": {
    "token": "a1b2c3d4...",
    "expiresAt": "2026-11-26T15:10:00+01:00",
    "officer": {
      "officerId": "off_new_001",
      "serviceNumber": "NPF-7001",
      "status": "on_duty",
      "mustChangePassword": false,
      "activatedAt": "2026-11-26T07:10:00+01:00"
    }
  }
}
```

The returned token can be used immediately with:

```http
GET /auth/me
Authorization: Bearer <token>
```

### Errors

| Status | Code | Meaning |
|---|---|---|
| `422` | `INVALID_INVITATION` | Token is invalid, already used, or account is not pending |
| `410` | `INVITATION_EXPIRED` | Activation token has expired |
| `422` | `VALIDATION_FAILED` | Password confirmation or field validation failed |

---

## 5. List pending activation officers

Existing officer list endpoint with the new status filter:

```http
GET /officers?status=pending_activation
Authorization: Bearer <admin-token>
```

```json
{
  "status": true,
  "message": "Officers and access.",
  "data": {
    "data": [
      {
        "officerId": "off_new_001",
        "serviceNumber": "NPF-7001",
        "fullName": "New Field Officer",
        "status": "pending_activation",
        "mustChangePassword": false,
        "activatedAt": null
      }
    ],
    "pagination": {
      "page": 1,
      "pageSize": 25,
      "totalItems": 1,
      "totalPages": 1
    }
  }
}
```

---

## Login behaviour after provisioning

```http
POST /auth/login
```

```json
{
  "serviceNumber": "NPF-7001",
  "password": "Activated@123"
}
```

Relevant errors:

| Status | Code | Meaning |
|---|---|---|
| `401` | `UNAUTHENTICATED` | Wrong service number or password |
| `403` | `ACCOUNT_NOT_ACTIVATED` | Account is still pending activation |
| `403` | `FORBIDDEN` | Account is suspended |
| `403` | `TEMPORARY_PASSWORD_EXPIRED` | Temporary password has expired |

When a temporary password is used successfully, the response officer object includes:

```json
{
  "mustChangePassword": true
}
```

The officer must change the password through:

```http
POST /field/profile/password
Authorization: Bearer <token>
```

```json
{
  "currentPassword": "Ntem-XY34-6789",
  "newPassword": "Permanent@123",
  "newPassword_confirmation": "Permanent@123"
}
```

After the change:

- `must_change_password` becomes `false`
- The temporary-password expiry is cleared
- Other sessions are revoked; the current session remains active

---

## Postman

The **Officer provisioning** folder in:

```text
postman/Manifest-Checker-API.postman_collection.json
```

contains these requests with saved example responses:

- Create officer with invite
- Create officer with temporary password
- List pending activation officers
- Resend activation invite
- Reset officer password
- Activate officer account
