package tn.nexus.Services;


import javax.activation.DataHandler;
import javax.activation.DataSource;
import javax.activation.FileDataSource;
import javax.mail.*;
import javax.mail.internet.InternetAddress;
import javax.mail.internet.MimeBodyPart;
import javax.mail.internet.MimeMessage;
import javax.mail.internet.MimeMultipart;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Paths;
import java.util.Properties;

public class EmailService {

    private final String username = "jlassimeriem2002@gmail.com"; // Remplace par ton email
    private final String password = "dbzo vsbf bczo mbip"; // Remplace par ton mot de passe d'application

    public void sendEmail(String to) {
        Properties props = new Properties();
        props.put("mail.smtp.auth", "true");
        props.put("mail.smtp.starttls.enable", "true");
        props.put("mail.smtp.host", "smtp.gmail.com");
        props.put("mail.smtp.port", "587");

        Session session = Session.getInstance(props, new Authenticator() {
            protected PasswordAuthentication getPasswordAuthentication() {
                return new PasswordAuthentication(username, password);
            }
        });

        try {
            // Lire le fichier HTML
            String htmlContent = new String(Files.readAllBytes(Paths.get("src/main/resources/Recrutement/templates/email_template.html")));

            // Création du message
            MimeMessage message = new MimeMessage(session);
            message.setFrom(new InternetAddress(username));
            message.setRecipients(Message.RecipientType.TO, InternetAddress.parse(to));
            message.setSubject("Bienvenue sur OptiRH !");

            // Création du multipart (contenu HTML + image)
            MimeMultipart multipart = new MimeMultipart("related");

            // Partie HTML
            MimeBodyPart htmlPart = new MimeBodyPart();
            htmlPart.setContent(htmlContent, "text/html; charset=utf-8");
            multipart.addBodyPart(htmlPart);

            // Partie Image (Logo)
            MimeBodyPart imagePart = new MimeBodyPart();
            DataSource fds = new FileDataSource("src/main/resources/Recrutement/images/logoGris_enhanced.png"); // Chemin de ton logo
            imagePart.setDataHandler(new DataHandler(fds));
            imagePart.setHeader("Content-ID", "<logo>"); // ID unique qui sera référencé dans le HTML (cid:logo)
            imagePart.setDisposition(MimeBodyPart.INLINE); // Définir l'image comme inline pour l'intégrer dans le contenu
            multipart.addBodyPart(imagePart);

            // Ajouter tout au message
            message.setContent(multipart);

            // Envoi du message
            Transport.send(message);
            System.out.println("✅ Email envoyé avec succès à " + to);

        } catch (MessagingException | IOException e) {
            e.printStackTrace();

        }
    }

    // Méthode pour envoyer l'email pour le statut "EN ATTENTE"
    public void sendWaitingEmail(String to) {
        Properties props = new Properties();
        props.put("mail.smtp.auth", "true");
        props.put("mail.smtp.starttls.enable", "true");
        props.put("mail.smtp.host", "smtp.gmail.com");
        props.put("mail.smtp.port", "587");

        Session session = Session.getInstance(props, new Authenticator() {
            protected PasswordAuthentication getPasswordAuthentication() {
                return new PasswordAuthentication(username, password);
            }
        });

        try {
            // Lire le fichier HTML
            String htmlContent = new String(Files.readAllBytes(Paths.get("src/main/resources/Recrutement/templates/email_attente.html")));

            // Création du message
            MimeMessage message = new MimeMessage(session);
            message.setFrom(new InternetAddress(username));
            message.setRecipients(Message.RecipientType.TO, InternetAddress.parse(to));
            message.setSubject("Réception de votre candidature");

            // Partie HTML (en attente)
            MimeMultipart multipart = new MimeMultipart("related");
            MimeBodyPart htmlPart = new MimeBodyPart();
            htmlPart.setContent(htmlContent, "text/html; charset=utf-8");
            multipart.addBodyPart(htmlPart);

            // Partie Image (Logo)
            MimeBodyPart imagePart = new MimeBodyPart();
            DataSource fds = new FileDataSource("src/main/resources/Recrutement/images/logoGris_enhanced.png"); // Chemin de ton logo
            imagePart.setDataHandler(new DataHandler(fds));
            imagePart.setHeader("Content-ID", "<logo>"); // ID unique qui sera référencé dans le HTML (cid:logo)
            imagePart.setDisposition(MimeBodyPart.INLINE); // Définir l'image comme inline pour l'intégrer dans le contenu
            multipart.addBodyPart(imagePart);

            // Ajouter tout au message
            message.setContent(multipart);

            // Envoi du message
            Transport.send(message);
            System.out.println("✅ E-mail de confirmation envoyé avec succès à " + to);

        } catch (MessagingException | IOException e) {
            e.printStackTrace();
        }
    }

        // Méthode pour envoyer l'email pour le statut "ACCEPTEE"
        public void sendAcceptedEmail(String to, String candidatNom, String poste) {
            try {
                String subject = "Candidature Acceptée";
                String templatePath = "src/main/resources/Recrutement/templates/email_acceptee.html";
                String body = new String(Files.readAllBytes(Paths.get(templatePath)));
                body = body.replace("${CANDIDAT}", candidatNom)
                        .replace("${POSTE}", poste);
                sendEmail(to, subject, body);
            } catch (IOException e) {
                e.printStackTrace();
            }
        }

        // Méthode pour envoyer l'email pour le statut "REFUSEE"
        public void sendRejectedEmail(String to, String candidatNom, String poste) {
            try {
                String subject = "Candidature Refusée";
                String templatePath = "src/main/resources/Recrutement/templates/email_refusee.html";
                String body = new String(Files.readAllBytes(Paths.get(templatePath)));
                body = body.replace("${CANDIDAT}", candidatNom)
                        .replace("${POSTE}", poste);
                sendEmail(to, subject, body);
            } catch (IOException e) {
                e.printStackTrace();
            }
        }

        // Méthode pour envoyer un email général
        public void sendEmail(String to, String subject, String body) {
            Properties props = new Properties();
            props.put("mail.smtp.auth", "true");
            props.put("mail.smtp.starttls.enable", "true");
            props.put("mail.smtp.host", "smtp.gmail.com");
            props.put("mail.smtp.port", "587");

            Session session = Session.getInstance(props, new Authenticator() {
                protected PasswordAuthentication getPasswordAuthentication() {
                    return new PasswordAuthentication(username, password);
                }
            });

            try {
                // Créer le message
                MimeMessage message = new MimeMessage(session);
                message.setFrom(new InternetAddress(username));
                message.setRecipients(Message.RecipientType.TO, InternetAddress.parse(to));
                message.setSubject(subject);

                // Création du multipart (contenu HTML + image)
                MimeMultipart multipart = new MimeMultipart("related");

                // Partie HTML : Utilisation du body dynamique (content généré par le statut)
                MimeBodyPart htmlPart = new MimeBodyPart();
                htmlPart.setContent(body, "text/html; charset=utf-8");
                multipart.addBodyPart(htmlPart);

                // Partie Image (Logo) : Ajouter un logo ou une image en ligne
                MimeBodyPart imagePart = new MimeBodyPart();
                DataSource fds = new FileDataSource("src/main/resources/Recrutement/images/logoGris_enhanced.png"); // Logo
                imagePart.setDataHandler(new DataHandler(fds));
                imagePart.setHeader("Content-ID", "<logo>"); // ID unique référencé dans le HTML
                imagePart.setDisposition(MimeBodyPart.INLINE); // Définir l'image comme inline
                multipart.addBodyPart(imagePart);

                // Ajouter tout au message
                message.setContent(multipart);

                // Envoi du message
                Transport.send(message);
                System.out.println("✅ Email envoyé avec succès à " + to);

            } catch (MessagingException e) {
                e.printStackTrace();
            }
        }

}



