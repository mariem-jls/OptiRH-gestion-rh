package tn.nexus.Services.reclamation;
import javax.mail.*;
import javax.mail.internet.InternetAddress;
import javax.mail.internet.MimeMessage;
import java.util.Properties;

public class EmailService {
    private static final String FROM_EMAIL = "maram.rachdi11@gmail.com";
    private static final String PASSWORD = "bjdx hdsy bxbi zhvt";
    private static final String TO_EMAIL = "maram.rachdi@eesprit.tn";

    public static void sendEmail(String subject, String content) {
        Properties props = new Properties();
        props.put("mail.smtp.host", "smtp.gmail.com");
        props.put("mail.smtp.port", "587");
        props.put("mail.smtp.auth", "true");
        props.put("mail.smtp.starttls.enable", "true");

        Session session = Session.getInstance(props, new Authenticator() {
            protected PasswordAuthentication getPasswordAuthentication() {
                return new PasswordAuthentication(FROM_EMAIL, PASSWORD);
            }
        });

        try {
            Message message = new MimeMessage(session);
            message.setFrom(new InternetAddress(FROM_EMAIL));
            message.setRecipients(Message.RecipientType.TO, InternetAddress.parse(TO_EMAIL));
            message.setSubject(subject);
            message.setContent(content, "text/html");

            Transport.send(message);
            System.out.println("E-mail envoyé avec succès !");
        } catch (MessagingException e) {
            e.printStackTrace();
        }
    }
}
