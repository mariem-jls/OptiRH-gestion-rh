package tn.nexus.Controllers.Evenement;

import com.twilio.Twilio;
import com.twilio.rest.api.v2010.account.Message;
import com.twilio.type.PhoneNumber;
import io.github.cdimascio.dotenv.Dotenv;

public class SmsService {
    static Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();
    private static final String ACCOUNT_SID = dotenv.get("AC48c9ba53ccbe73b030ad7d3084c38062");
    private static final String AUTH_TOKEN = dotenv.get("f6c460a607cbde4e08e99849ab4b7544");
    private static final String TWILIO_PHONE_NUMBER = dotenv.get("+17069189376"); // Ex: +123456789

    static {
        Twilio.init(ACCOUNT_SID, AUTH_TOKEN);
    }

    public static void sendSms(String to, String message) {
        try {
            Message.creator(
                    new PhoneNumber(to),   // Numéro du destinataire
                    new PhoneNumber(TWILIO_PHONE_NUMBER),  // Numéro Twilio
                    message // Contenu du SMS
            ).create();
            System.out.println("SMS envoyé avec succès à " + to);
        } catch (Exception e) {
            System.out.println("Erreur lors de l'envoi du SMS : " + e.getMessage());
        }
    }
}
