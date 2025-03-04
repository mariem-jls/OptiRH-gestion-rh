package tn.nexus.Services.Transport;

import jakarta.mail.*;
import jakarta.mail.internet.InternetAddress;
import jakarta.mail.internet.MimeMessage;

import java.util.Properties;

public class MailService {

    private final String username; // Votre adresse e-mail
    private final String password; // Votre mot de passe
    private final Properties props;

    public MailService() {
        this.username = "bouhanioumaima2@gmail.com"; // Remplacez par votre e-mail
        this.password = "rzvpdskxqxvmqyck"; // Remplacez par votre mot de passe

        // Configuration des propriétés pour Gmail
        props = new Properties();
        props.put("mail.smtp.auth", "true");
        props.put("mail.smtp.starttls.enable", "true");
        props.put("mail.smtp.host", "smtp.gmail.com");
        props.put("mail.smtp.port", "587");
    }

    public void sendReservationConfirmation(String toEmail, String typeVehicule, String pointDepart, String pointArrivee, String station) {
        Session session = Session.getInstance(props, new Authenticator() {
            @Override
            protected PasswordAuthentication getPasswordAuthentication() {
                return new PasswordAuthentication(username, password);
            }
        });

        try {
            Message message = new MimeMessage(session);
            message.setFrom(new InternetAddress(username));
            message.setRecipients(Message.RecipientType.TO, InternetAddress.parse(toEmail));
            message.setSubject("Confirmation de Réservation - OPTIRH");

            // Contenu HTML amélioré
            String htmlContent = "<html>"
                    + "<body style='font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;'>"
                    + "<div style='max-width: 600px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0px 0px 10px #cccccc; margin: auto;'>"
                    + "<div style='text-align: center; padding-bottom: 20px;'>"
                    + "<img src='https://via.placeholder.com/150' alt='Logo OPTIRH' style='max-width: 150px; border-radius: 8px;' />"
                    + "</div>"
                    + "<h2 style='color: #0056b3; text-align: center;'>OPTIRH - Confirmation de Réservation</h2>"
                    + "<p style='color: #333; font-size: 16px; text-align: center;'>Merci d'avoir réservé avec <strong>OPTIRH</strong>. Voici les détails de votre réservation :</p>"
                    + "<div style='background: #f8f8f8; padding: 15px; border-radius: 8px; margin-top: 20px;'>"
                    + "<p><strong> Type de Véhicule :</strong> " + typeVehicule + "</p>"
                    + "<p><strong> Point de Départ :</strong> " + pointDepart + "</p>"
                    + "<p><strong> Point d'Arrivée :</strong> " + pointArrivee + "</p>"
                    + "<p><strong> Station :</strong> " + station + "</p>"
                    + "</div>"
                    + "<p style='text-align: center; margin-top: 20px; font-size: 14px; color: #666;'>Nous vous remercions pour votre confiance et vous souhaitons un excellent trajet !</p>"
                    + "<hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;' />"
                    + "<p style='text-align: center; font-size: 12px; color: #999;'>© 2024 OPTIRH. Tous droits réservés.</p>"
                    + "</div>"
                    + "</body>"
                    + "</html>";

            message.setContent(htmlContent, "text/html");

            Transport.send(message);
            System.out.println("E-mail envoyé avec succès à " + toEmail);
        } catch (MessagingException e) {
            System.err.println("Erreur lors de l'envoi de l'e-mail : " + e.getMessage());
        }
    }

}