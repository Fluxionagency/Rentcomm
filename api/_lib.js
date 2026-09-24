// Rentcom — shared helpers for the Vercel serverless lead-capture functions.
//
// Phase 1 (this file): forms are validated here and emailed to the team via
// Resend. There is no database yet — that's Phase 2 (a hosted Postgres +
// a rebuilt Admin Portal). A failed Resend send means the lead has no
// record anywhere, so if a lead ever seems to go missing, check Vercel's
// function logs (Deployments → a deployment → Functions) for
// "Resend send failed" / "not set" errors below.
//
// Mirrors the validation in public_html/api/db.php so the two backends
// (this one, and the PHP one used on Qserver) behave identically.

function respond(res, status, payload) {
  res.status(status).json(payload);
}

function validateLead(body) {
  const name = String(body.name || '').trim();
  const email = String(body.email || '').trim();
  const whatsapp = String(body.whatsapp || '').trim();

  if (!name || name.length > 120) {
    return { error: 'Please enter your name.' };
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) || email.length > 190) {
    return { error: 'Please enter a valid email address.' };
  }
  if (!/^[+0-9][0-9 ()\-]{6,24}$/.test(whatsapp)) {
    return { error: 'Please enter a valid WhatsApp number.' };
  }
  return { name, email, whatsapp };
}

async function notifyTeam(subject, text) {
  const apiKey = process.env.RESEND_API_KEY;
  const to = process.env.NOTIFY_EMAIL;
  const from = process.env.MAIL_FROM || 'Rentcom Website <onboarding@resend.dev>';

  if (!apiKey || !to) {
    // Fail loud in the logs — with no database yet, this is the only trace
    // that a lead came in and was never actually delivered to the team.
    console.error(
      'Rentcom: RESEND_API_KEY and/or NOTIFY_EMAIL not set in Vercel env vars — ' +
        'lead was validated but NOT emailed. Subject: ' + subject
    );
    return;
  }

  try {
    const res = await fetch('https://api.resend.com/emails', {
      method: 'POST',
      headers: {
        Authorization: 'Bearer ' + apiKey,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ from, to, subject, text }),
    });
    if (!res.ok) {
      console.error('Rentcom: Resend send failed (' + res.status + '): ' + (await res.text()));
    }
  } catch (err) {
    console.error('Rentcom: Resend request threw:', err);
  }
}

module.exports = { respond, validateLead, notifyTeam };
