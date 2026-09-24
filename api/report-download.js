// Vercel serverless equivalent of public_html/api/report-download.php.
// Handles the free-report download form on the Homepage and Report Page.
// See api/_lib.js for the Phase 1 (no-database) caveat.
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
    'New report download lead: ' + lead.name,
    'The Lagos Rent Report 2026 was requested.\n\n' +
      'Name: ' + lead.name + '\nEmail: ' + lead.email + '\nWhatsApp: ' + lead.whatsapp + '\n\n' +
      '(Phase 1: not yet saved to a database — this email is the only record.)'
  );

  // TODO (same as the PHP version): once the real report PDF exists, attach
  // or link it in a confirmation email to the lead here.

  respond(res, 200, { ok: true });
};
