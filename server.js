const express = require('express');
const nodemailer = require('nodemailer');
const bodyParser = require('body-parser');
const cors = require('cors');
const rateLimit = require('express-rate-limit');


const limiter = rateLimit({
  windowMs: 1 * 60 * 1000, // 1 minute
  max: 5, // limit each IP to 5 requests per minute
  message: { success: false, message: "Too many requests, please try again later." }
});
const app = express();
app.use(cors());
app.use(bodyParser.json());
require('dotenv').config();

app.post('/send-mail',limiter,  async (req, res) => {

  if (req.body.website && req.body.website.trim() !== "") {
  return res.status(400).json({ success: false, message: 'Spam detected' });
}

  const { name, email, tel, budget, message } = req.body;

  if (!name || !email || !tel || !budget || !message) {
    return res.status(400).json({ success: false, message: 'All fields are required' });
  }

  if (!/^\d{10,15}$/.test(tel)) {
    return res.status(400).json({ success: false, message: 'Phone must be Proper' });
  }

  if (!/^\d+$/.test(budget)) {
    return res.status(400).json({ success: false, message: 'Budget must be numbers only' });
  }

  if (message.length < 10) {
    return res.status(400).json({ success: false, message: 'Message must be at least 10 characters' });
  }

  const transporter = nodemailer.createTransport({
    host: 'smtp.hostinger.com',
    port: 465,
    secure: true,
    auth: {
      user: process.env.EMAIL_USER,
      pass: process.env.EMAIL_PASS
    }
  });

  const mailOptions = {
    from: `"${name}" <aaradhya@niilkanth.com>`,
    to: process.env.EMAIL_TO,
    subject: `New Contact Form Submission from Aaradhya Site ${name}`,
    text: `
      Name: ${name}
      Email: ${email}
      Phone: ${tel}
      Budget: ${budget}
      Message: ${message}
    `
  };

  try {
    await transporter.sendMail(mailOptions);
    res.json({ success: true, message: 'Message sent successfully!' });
  } catch (error) {
    console.error(error);
    res.status(500).json({ success: false, message: 'Failed to send email. Please try again later.' });
  }
});


const PORT = process.env.PORT || 5000;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
