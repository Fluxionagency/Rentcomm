// Vercel serverless equivalent of public_html/api/investor-enquiry.php.
// Handles the Investor Journey, Portfolio Detail, and Workforce Apartments
// enquiry forms. See api/_lib.js for the Phase 1 (no-database) caveat.
const { respond, validateLead, notifyTeam } = require('./_lib');

module.exports = async (req, res) => {
  if (req.method !== 'POST') {
    return respond(res, 405, { ok: false, error: 'Method not allowed.' });
  }

  const body = req.body || {};

  // Honeypot: real users never fill this hidden field. Pretend success so
  // bots don't learn they were caught (matches the PHP behavior).
  if (body.website) {
    return respond(res, 200, { ok: true });
  }

  const lead = validateLead(body);
  if (lead.error) {
    return respond(res, 422, { ok: false, error: lead.error });
  }

  let source = String(body.source || 'investor-journey').trim().slice(0, 80);
  if (!source) {
    source = 'investor-journey';
  }

  await notifyTeam(
    'New investor enquiry: ' + lead.name,
    'An investor enquiry was submitted (' + source + ').\n\n' +
      'Name: ' + lead.name + '\nEmail: ' + lead.email + '\nWhatsApp: ' + lead.whatsapp + '\n\n' +
      '(Phase 1: not yet saved to a database — this email is the only record.)'
  );

  respond(res, 200, { ok: true });
};
