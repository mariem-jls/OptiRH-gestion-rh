package tn.nexus.Utils.Mission;

// Remplacer tous les imports jakarta par javax
import javax.activation.DataHandler;
import javax.mail.*;
import javax.mail.internet.*;
import javax.mail.util.ByteArrayDataSource;
import tn.nexus.Entities.Mission.Mission;

import java.io.InputStream;
import java.io.UnsupportedEncodingException;
public class EmailSender {
    private static final String LOGO_RESOURCE_PATH = "/Img/logoGris_enhanced.png";
    private static final String FROM_EMAIL = "bouhanioumaima2@gmail.com";
    private static final String FROM_NAME = "Optirh Notifications";
    public void sendDeadlineAlert(Mission mission, String toEmail) {
        try {
            MimeMessage message = createEmailStructure(mission, toEmail, "[URGENT] Échéance proche: " + mission.getTitre());
            MimeMultipart multipart = createAlertContent(mission);
            message.setContent(multipart);
            Transport.send(message);
            System.out.println("Alerte envoyée à " + toEmail);
        } catch (Exception e) {
            System.err.println("Erreur d'envoi d'alerte : ");
            e.printStackTrace();
        }
    }

    private MimeMultipart createAlertContent(Mission mission) throws Exception {
        MimeMultipart multipart = new MimeMultipart("related");

        // Partie HTML
        MimeBodyPart htmlPart = new MimeBodyPart();
        htmlPart.setContent(buildAlertHtml(mission), "text/html; charset=UTF-8");
        multipart.addBodyPart(htmlPart);

        // Logo intégré (réutilisation de la méthode existante)
        multipart.addBodyPart(createLogoPart());

        return multipart;
    }

    private String buildAlertHtml(Mission mission) {
        return "<!DOCTYPE html>" +
                "<html>" +
                "<head>" +
                "<meta charset='UTF-8'>" +
                "<style>" +
                "body { font-family: Arial, sans-serif; max-width: 600px; margin: auto; }" +
                ".header { text-align: center; padding: 20px; background-color: #fff3cd; }" +
                ".content { padding: 30px 20px; }" +
                ".urgent { color: #dc3545; font-weight: bold; }" +
                "</style>" +
                "</head>" +
                "<body>" +
                "<div class='header'>" +
                "<img src='cid:logoImage' alt='Logo Optirh' style='height: 60px;'>" +
                "<h1 style='color: #dc3545;'>⚠ Échéance Approchante</h1>" +
                "</div>" +
                "<div class='content'>" +
                "<p>Bonjour,</p>" +
                "<p class='urgent'>La mission ci-dessous arrive à échéance bientôt :</p>" +
                "<ul>" +
                "<li><strong>Titre :</strong> " + mission.getTitre() + "</li>" +
                "<li><strong>Date limite :</strong> " + mission.getDateTerminer() + "</li>" +
                "<li><strong>Statut actuel :</strong> " + mission.getStatus() + "</li>" +
                "</ul>" +
                "<p>Veuillez prendre les mesures nécessaires pour respecter le délai.</p>" +
                "<p>Cordialement,<br><strong>L'équipe Nexus</strong></p>" +
                "</div>" +
                "</body>" +
                "</html>";
    }

    // Méthode helper réutilisée
    private MimeMessage createEmailStructure(Mission mission, String toEmail, String subject) throws MessagingException, UnsupportedEncodingException {
        MimeMessage message = new MimeMessage(MailConfig.getSession());
        message.setFrom(new InternetAddress(FROM_EMAIL, FROM_NAME));
        message.setRecipient(Message.RecipientType.TO, new InternetAddress(toEmail));
        message.setSubject(subject);
        return message;
    }
    public void sendNewMissionNotification(Mission mission, String toEmail) {
        try {
            MimeMessage message = createEmailStructure(mission, toEmail);
            MimeMultipart multipart = createEmailContent(mission);
            message.setContent(multipart);
            Transport.send(message);
            System.out.println("Email envoyé avec succès à " + toEmail);
        } catch (Exception e) {
            System.err.println("Erreur d'envoi de notification : ");
            e.printStackTrace(); // Ajout du stack trace pour le débogage
        }
    }

    private MimeMessage createEmailStructure(Mission mission, String toEmail) throws MessagingException, UnsupportedEncodingException {
        MimeMessage message = new MimeMessage(MailConfig.getSession());
        message.setFrom(new InternetAddress(FROM_EMAIL, FROM_NAME));
        message.setRecipient(Message.RecipientType.TO, new InternetAddress(toEmail));
        message.setSubject("[NEXUS] Nouvelle Mission Assignée : " + mission.getTitre());
        return message;
    }

    private MimeMultipart createEmailContent(Mission mission) throws Exception {
        MimeMultipart multipart = new MimeMultipart("related");

        // Partie HTML
        MimeBodyPart htmlPart = new MimeBodyPart();
        htmlPart.setContent(buildHtmlContent(mission), "text/html; charset=UTF-8");
        multipart.addBodyPart(htmlPart);

        // Partie image intégrée
        multipart.addBodyPart(createLogoPart());

        return multipart;
    }

    private String buildHtmlContent(Mission mission) {
        return "<!DOCTYPE html>" +
                "<html>" +
                "<head>" +
                "<meta charset='UTF-8'>" +
                "<style>" +
                "body { font-family: Arial, sans-serif; max-width: 600px; margin: auto; }" +
                ".header { text-align: center; padding: 20px; background-color: #f8f9fa; }" +
                ".content { padding: 30px 20px; }" +
                "</style>" +
                "</head>" +
                "<body>" +
                "<div class='header'>" +
                "<img src='cid:logoImage' alt='Logo Optirh' style='height: 60px;'>" +
                "</div>" +
                "<div class='content'>" +
                "<h2 style='color: #2c3e50;'>Nouvelle Mission Assignée</h2>" +
                "<p>Bonjour,</p>" +
                "<p>Vous avez été assigné(e) à une nouvelle mission :</p>" +
                "<ul>" +
                "<li><strong>Titre :</strong> " + mission.getTitre() + "</li>" +
                "<li><strong>Description :</strong> " + mission.getDescription() + "</li>" +
                "<li><strong>Date limite :</strong> " + mission.getDateTerminer() + "</li>" +
                "<li><strong>Statut initial :</strong> " + mission.getStatus() + "</li>" +
                "</ul>" +
                "<p>Cordialement,<br><strong>L'équipe Nexus</strong></p>" +
                "</div>" +
                "</body>" +
                "</html>";
    }

    private MimeBodyPart createLogoPart() throws Exception {
        MimeBodyPart imagePart = new MimeBodyPart();
        try (InputStream logoStream = getClass().getResourceAsStream(LOGO_RESOURCE_PATH)) {
            if (logoStream == null) {
                throw new IllegalStateException("Logo introuvable : " + LOGO_RESOURCE_PATH);
            }

            imagePart.setDataHandler(new DataHandler(new ByteArrayDataSource(
                    logoStream.readAllBytes(),
                    "image/png"
            )));

            imagePart.setHeader("Content-ID", "<logoImage>");
            imagePart.setDisposition(MimeBodyPart.INLINE);
        }
        return imagePart;
    }

    // Méthode sendDeadlineAlert reste inchangée
}