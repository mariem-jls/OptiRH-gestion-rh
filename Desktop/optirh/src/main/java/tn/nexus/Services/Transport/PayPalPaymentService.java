package tn.nexus.Services.Transport;
import com.paypal.api.payments.*;
import com.paypal.base.rest.APIContext;
import com.paypal.base.rest.PayPalRESTException;

import java.util.ArrayList;
import java.util.List;

public class PayPalPaymentService {
    private static final String CLIENT_ID = "Ae0e-pqgdZLdSLCwYRkELsVgO2HQh8RcblD2Yic6yiWnTJJoNLk9NR7up5m2JrgINAH7FGq8hwi2m4UR";
    private static final String CLIENT_SECRET = "ED5es733gBYWdMmR6CpTu9yHXQZL1g9iTXm88IQYtYbbmIwNNm7LzM-SoWgNniKMLFCbP7YM2dUuL6-V";
    private static final String MODE = "sandbox";

    public String createPayment(double amount, String currency, String description) {
        APIContext apiContext = new APIContext(CLIENT_ID, CLIENT_SECRET, MODE);

        Amount paymentAmount = new Amount();
        paymentAmount.setCurrency(currency);
        paymentAmount.setTotal(String.format("%.2f", amount));

        Transaction transaction = new Transaction();
        transaction.setAmount(paymentAmount);
        transaction.setDescription(description);

        List<Transaction> transactions = new ArrayList<>();
        transactions.add(transaction);

        Payer payer = new Payer();
        payer.setPaymentMethod("paypal");

        Payment payment = new Payment();
        payment.setIntent("sale");
        payment.setPayer(payer);
        payment.setTransactions(transactions);

        RedirectUrls redirectUrls = new RedirectUrls();
        redirectUrls.setCancelUrl("http://example.com/cancel");
        redirectUrls.setReturnUrl("http://example.com/success");
        payment.setRedirectUrls(redirectUrls);

        try {
            Payment createdPayment = payment.create(apiContext);
            System.out.println("Payment ID: " + createdPayment.getId());

            // Récupérer l'URL d'approbation
            for (Links link : createdPayment.getLinks()) {
                if (link.getRel().equalsIgnoreCase("approval_url")) {
                    // Ajouter le paramètre de langue à l'URL d'approbation
                    String approvalUrl = link.getHref();
                    approvalUrl += "&locale=fr_FR"; // Forcer le français
                    return approvalUrl;
                }
            }
        } catch (PayPalRESTException e) {
            e.printStackTrace();
        }
        return null;
    }
}