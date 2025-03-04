package tn.nexus.Utils.Mission;

import javax.mail.*;
import java.util.Properties;

public class MailConfig {
    public static Session getSession() {
        Properties props = new Properties();
        props.put("mail.transport.protocol", "smtp"); // Obligatoire
        props.put("mail.smtp.auth", "true");
        props.put("mail.smtp.starttls.enable", "true");
        props.put("mail.smtp.host", "smtp.gmail.com");
        props.put("mail.smtp.port", "587");
        props.put("mail.smtp.ssl.trust", "smtp.gmail.com");
        props.put("mail.debug", "true"); // Pour le diagnostic

        return Session.getInstance(props, new javax.mail.Authenticator() {
            protected PasswordAuthentication getPasswordAuthentication() {
                return new PasswordAuthentication(
                        "bouhanioumaima2@gmail.com",
                        "rzvpdskxqxvmqyck"
                );
            }
        });
    }
}