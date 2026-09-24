// Vercel serverless equivalent of public_html/api/realtor-register.php.
// Handles the Realtor Journey registration form. See api/_lib.js for the
// Phase 1 (no-database) caveat.
//
// Note: unlike the PHP version, this cannot create a dispatch-tracker row
// (there's no database yet), so the "unlock the Marketing Asset Portal"
// behavior on realtor.html — which runs entirely client-side via
// localStorage — still works, but the invite-pack dispatch has to be
// tracked manually from the notification email until Phase 2.
const { respond, validateLead, notifyTeam } = require('./_lib');

module.exports = async (req, res) => {
  if (req.method !== 'POST') {
    return respond(res, 405, { ok: false, error: 'Method not allowed.' });
  }

  const body = req.body || {};

  if (body.website) {
    return respond(res, 200, { ok: true });
  }

  const lead = validateLead(body);
  if (lead.error) {
    return respond(res, 422, { ok: false, error: lead.error });
  }

  await notifyTeam(
    'New realtor registration: ' + lead.name,
    'A realtor registered for the Fadayee campaign.\n\n' +
      'Name: ' + lead.name + '\nEmail: ' + lead.email + '\nWhatsApp: ' + lead.whatsapp + '\n\n' +
      'Remember to dispatch their physical invite pack.\n\n' +
      '(Phase 1: not yet saved to a database — this email is the only record.)'
  );

  respond(res, 200, { ok: true });
};
