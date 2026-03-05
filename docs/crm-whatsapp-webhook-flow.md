# CRM WhatsApp Webhook Flow

## Endpoint

- `GET /api/webhooks/whatsapp`: webhook verification (`hub.mode`, `hub.verify_token`, `hub.challenge`)
- `POST /api/webhooks/whatsapp`: inbound webhook processing

## Core Behavior

1. If first WhatsApp message arrives from unknown contact:
   - Create `contacts` record
   - Create `contact_identities` record (`platform=whatsapp`)
   - Create `leads` record (`stage=initial`, `status=open`)
   - Create `lead_activities` record (`message_received`)
   - Create `follow_ups` record (`trigger_type=auto_first_message`)

2. If contact sends another message later:
   - Keep same open lead
   - Add new `lead_activities` record
   - Add new `follow_ups` record (`trigger_type=auto_inbound_message`)

3. If previous lead is closed:
   - Reopen if inside reopen window (`CRM_LEAD_REOPEN_WINDOW_DAYS`)
   - Otherwise create a new lead

4. Duplicate control:
   - `webhook_events` stores inbound event IDs (`platform + event_id` unique)
   - `lead_activities` has unique `(platform, platform_message_id)`

## Key Tables

- `contacts`
- `contact_identities`
- `leads`
- `lead_activities`
- `follow_ups`
- `webhook_events`
