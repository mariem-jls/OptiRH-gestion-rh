
package tn.nexus.Utils.Mission;

import java.util.Properties;
import jakarta.mail.Session;

public class MailConfig {
    public static Session getSession() {
        Properties props = new Properties();
        props.put("mail.smtp.auth", "true");
        props.put("mail.smtp.starttls.enable", "true");
        props.put("mail.smtp.host", "smtp.gmail.com");
        props.put("mail.smtp.port", "587");

        return Session.getInstance(props, new jakarta.mail.Authenticator() {
            protected jakarta.mail.PasswordAuthentication getPasswordAuthentication() {
                return new jakarta.mail.PasswordAuthentication("bouhanioumaima2@gmail.com", "rzvpdskxqxvmqyck");
            }
        });
    }
}