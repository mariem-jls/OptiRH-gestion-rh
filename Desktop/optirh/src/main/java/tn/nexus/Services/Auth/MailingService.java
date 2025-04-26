package tn.nexus.Services.Auth;

import org.simplejavamail.api.email.Email;
import org.simplejavamail.api.mailer.Mailer;
import org.simplejavamail.api.mailer.config.TransportStrategy;
import org.simplejavamail.email.EmailBuilder;
import org.simplejavamail.mailer.MailerBuilder;

import io.github.cdimascio.dotenv.Dotenv;
import javafx.scene.control.Alert;
import tn.nexus.Entities.User;

public class MailingService {
    Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();
    private final String MAIL_ADDRESS = dotenv.get("MAIL_ADDRESS");
    private final String MAIL_PASSWORD = dotenv.get("MAIL_PASSWORD");
    private final int MAIL_PORT = Integer.parseInt(dotenv.get("MAIL_PORT"));
    private final String MAIL_HOST = dotenv.get("MAIL_HOST");

    public void sendEmail(User user) {
        try {
            // Configuration du serveur SMTP (exemple avec Gmail)
            Mailer mailer = MailerBuilder
                    .withSMTPServer(MAIL_HOST, MAIL_PORT, MAIL_ADDRESS, MAIL_PASSWORD)
                    .withTransportStrategy(TransportStrategy.SMTP_TLS)
                    .buildMailer();

            // Création de l'email
            Email email = EmailBuilder.startingBlank()
                    .from(MAIL_ADDRESS)
                    .to(user.getEmail())
                    .withSubject("Bienvenue " + user.getNom())
                    .withHTMLText("<html>" +
                            "<head>" +
                            "<style>" +
                            "body { font-family: Arial, sans-serif; line-height: 1.6; }" +
                            ".header { text-align: center; }" +
                            ".logo { width: 150px; }" +
                            ".content { margin: 20px; }" +
                            ".footer { margin-top: 30px; font-size: small; color: gray; }" +
                            "</style>" +
                            "</head>" +
                            "<body>" +
                            "<div class='header'>" +
                            "<img src='https://i.ibb.co/6cj94cTM/Opti-RH-finale.png' class='logo' alt='Company Logo' />"
                            +
                            "<h1>Bienvenue sur notre plateforme!</h1>" +
                            "</div>" +
                            "<div class='content'>" +
                            "<p>Bonjour " + user.getNom() + ",</p>" +
                            "<p>Bienvenue sur notre plateforme. Votre compte a été créé avec succès.</p>" +
                            "<p>Vous avez le rôle suivant : <strong>" + user.getRole() + "</strong>.</p>" +
                            "<p>Nous vous remercions de votre confiance.</p>" +
                            "</div>" +
                            "<div class='footer'>" +
                            "<p>Cordialement,</p>" +
                            "<p>L'équipe OptiRH</p>" +
                            "</div>" +
                            "</body>" +
                            "</html>")
                    .buildEmail();

            System.out.println("Email envoyé avec succès à " + user.getEmail());
            // Envoi de l'email
            mailer.sendMail(email);
        } catch (Exception e) {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Erreur d'envoi d'email");
            alert.setHeaderText(null);
            alert.setContentText("L'email n'a pas pu être envoyé : " + e.getMessage());
            alert.showAndWait();
        }
    }

    public void sendVerificationCode(String toEmail, String verificationCode) {
        try {
            // Configuration du serveur SMTP (exemple avec Gmail)
            Mailer mailer = MailerBuilder
                    .withSMTPServer(MAIL_HOST, MAIL_PORT, MAIL_ADDRESS, MAIL_PASSWORD)
                    .withTransportStrategy(TransportStrategy.SMTP_TLS)
                    .buildMailer();

            // Création de l'email
            Email email = EmailBuilder.startingBlank()
                    .from(MAIL_ADDRESS)
                    .to(toEmail)
                    .withSubject("Code de vérification pour réinitialisation du mot de passe")
                    .withHTMLText("<html>" +
                            "<head>" +
                            "<style>" +
                            "body { font-family: Arial, sans-serif; line-height: 1.6; }" +
                            ".header { text-align: center; }" +
                            ".logo { width: 150px; }" +
                            ".content { margin: 20px; }" +
                            ".footer { margin-top: 30px; font-size: small; color: gray; }" +
                            "</style>" +
                            "</head>" +
                            "<body>" +
                            "<div class='header'>" +
                            "<img src='https://i.ibb.co/6cj94cTM/Opti-RH-finale.png' class='logo' alt='Company Logo' />"
                            +
                            "<h1>Réinitialisation du mot de passe</h1>" +
                            "</div>" +
                            "<div class='content'>" +
                            "<p>Votre code de vérification est : <strong>" + verificationCode + "</strong></p>" +
                            "<p>Veuillez saisir ce code dans l'application pour réinitialiser votre mot de passe.</p>" +
                            "<p>Si vous n'avez pas demandé de réinitialisation de mot de passe, veuillez ignorer cet email.</p>"
                            +
                            "</div>" +
                            "<div class='footer'>" +
                            "<p>Cordialement,</p>" +
                            "<p>L'équipe OptiRH</p>" +
                            "</div>" +
                            "</body>" +
                            "</html>")
                    .buildEmail();

            // Envoi de l'email
            mailer.sendMail(email);
            System.out.println("Email de vérification envoyé avec succès à " + toEmail);
        } catch (Exception e) {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Erreur d'envoi d'email");
            alert.setHeaderText(null);
            alert.setContentText("L'email de vérification n'a pas pu être envoyé : " + e.getMessage());
            alert.showAndWait();
        }
    }
}
