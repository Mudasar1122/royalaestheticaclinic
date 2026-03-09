# CRM WhatsApp Webhook Flow (Twilio)

## Endpoints

- `GET /api/webhooks/whatsapp`
  - Health-check endpoint (returns `OK`)
  - Still supports Meta verification params (`hub.mode`, `hub.verify_token`, `hub.challenge`) if used
- `POST /api/webhooks/whatsapp`
  - Twilio inbound WhatsApp webhook receiver
- `POST /clinic/leads/{lead}/whatsapp/send`
  - Outbound WhatsApp message send from CRM using Twilio

## Required Env Variables

- `TWILIO_ACCOUNT_SID`
- `TWILIO_AUTH_TOKEN`
- `TWILIO_WHATSAPP_FROM` (example: `whatsapp:+14155238886`)
- `TWILIO_WHATSAPP_VALIDATE_SIGNATURE` (`true` in production)
- Optional: `TWILIO_WHATSAPP_STATUS_CALLBACK`

## Inbound Behavior

1. Twilio sends inbound WhatsApp message to `POST /api/webhooks/whatsapp`.
2. Signature is validated using `X-Twilio-Signature` and `TWILIO_AUTH_TOKEN` (when enabled).
3. Duplicate event protection:
   - `webhook_events` unique key: `platform + event_id`
4. Contact + lead resolution:
   - If this number has no lead: create lead.
   - If lead already exists for this number: reuse that lead (no new lead).
5. Follow-up auto creation:
   - Creates follow-up immediately (`due_at = now()`).
   - For existing leads, stage uses previous follow-up stage (`stage_snapshot` from latest follow-up).
6. If auto follow-up creation fails:
   - Event is processed with warning.
   - Notification is highlighted as "Manual Follow-up Required".

## Follow-up Remarks to Customer

- While updating follow-up status in appointments:
  - Add remarks
  - Enable "Send remarks to customer via Twilio WhatsApp"
- System sends the remark to customer and logs it in `lead_activities` as `follow_up_remark_sent`.

## Notification Highlighting

- Bell notifications now come from `webhook_events` for WhatsApp.
- Highlighted alerts appear when:
  - Webhook failed, or
  - Auto follow-up could not be created and manual action is required.
