<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur ConsultNow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fffe;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
            filter: brightness(0) invert(1);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
        }

        .greeting {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
        }

        .message {
            font-size: 16px;
            color: #4b5563;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        .credentials-box {
            background: linear-gradient(135deg, #f0fdf4, #ecfdf5);
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 25px;
            margin: 30px 0;
        }

        .credentials-title {
            color: #059669;
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .credentials-title::before {
            content: "🔑";
            margin-right: 8px;
            font-size: 20px;
        }

        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #d1fae5;
        }

        .credential-item:last-child {
            border-bottom: none;
        }

        .credential-label {
            font-weight: 600;
            color: #374151;
        }

        .credential-value {
            background-color: #ffffff;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            margin: 25px 0;
            border-radius: 8px;
        }

        .warning-box .warning-icon {
            color: #f59e0b;
            font-weight: bold;
            margin-right: 8px;
        }

        .warning-text {
            color: #92400e;
            font-weight: 500;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            text-decoration: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
            transition: all 0.3s ease;
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer-text {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .team-signature {
            color: #059669;
            font-weight: 600;
            font-size: 16px;
        }

        .divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, #10b981, transparent);
            margin: 30px 0;
        }

        @media (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 8px;
            }

            .header, .content, .footer {
                padding: 25px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .credential-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .credential-value {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="email-container">
    <!-- Header -->
    <div class="header">
        <h1>Bienvenue sur ConsultNow</h1>
        <p>Votre plateforme de consultation médicale</p>
    </div>

    <!-- Content -->
    <div class="content">
        <div class="greeting">Bonjour,</div>

        <div class="message">
            Nous sommes ravis de vous accueillir sur <strong>ConsultNow</strong> ! Votre compte invité a été créé avec succès et vous pouvez maintenant accéder à notre plateforme de consultation médicale.
        </div>

        <div class="credentials-box">
            <div class="credentials-title">
                Vos informations de connexion
            </div>

            <div class="credential-item">
                <span class="credential-label">Email :</span>
                <span class="credential-value">{{ $email }}</span>
            </div>

            <div class="credential-item">
                <span class="credential-label">Mot de passe temporaire :</span>
                <span class="credential-value">{{ $password }}</span>
            </div>
        </div>

        <div class="warning-box">
            <span class="warning-icon">⚠️</span>
            <span class="warning-text">
                    Pour votre sécurité, veuillez vous connecter et modifier votre mot de passe dès que possible.
                </span>
        </div>

        <div style="text-align: center;">
            <a href="https://consultnow.adlogistique-tms.xyz/auth/login" class="cta-button">Se connecter maintenant</a>
        </div>

        <div class="divider"></div>

        <div class="message">
            Si vous avez des questions ou besoin d'assistance, notre équipe support est à votre disposition.
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-text">
            Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.
        </div>
        <div class="team-signature">
            L'équipe ConsultNow
        </div>
    </div>
</div>
</body>
</html>
