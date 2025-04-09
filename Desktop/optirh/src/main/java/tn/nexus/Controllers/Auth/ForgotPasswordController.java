package tn.nexus.Controllers.Auth;

import java.io.IOException;
import java.sql.SQLException;
import java.util.Optional;
import java.util.Random;

import io.github.palexdev.materialfx.controls.MFXButton;
import io.github.palexdev.materialfx.controls.MFXTextField;
import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.TextInputDialog;
import javafx.scene.layout.VBox;
import javafx.scene.control.Alert;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.fxml.FXMLLoader;
import javafx.stage.Stage;
import tn.nexus.Entities.User;
import tn.nexus.Services.UserService;
import tn.nexus.Services.Auth.MailingService;

public class ForgotPasswordController {

    @FXML
    private MFXTextField emailField;

    @FXML
    private MFXTextField verificationCodeField;

    @FXML
    private Label statusLabel;

    @FXML
    private MFXButton sendVerificationCodeButton;

    @FXML
    private MFXButton resetPasswordButton;

    UserService userService = new UserService();
    MailingService mailingService = new MailingService();
    private String verificationCode;

    @FXML
    private VBox verificationCodeContainer;
    @FXML
    private MFXButton codeButton;
    @FXML
    private VBox emailBox;
    @FXML
    private MFXButton emailButton;

    @FXML
    void sendVerificationCode() {
        String email = emailField.getText().trim();

        if (email.isEmpty()) {
            statusLabel.setText("Veuillez entrer votre adresse e-mail.");
            return;
        }

        if (!email.matches("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$")) {
            statusLabel.setText("L'email n'est pas valide.");
            return;
        }

        try {
            User user = userService.getUserByEmail(email);
            if (user != null) {
                // Generate a random verification code
                verificationCode = generateVerificationCode();
                mailingService.sendVerificationCode(email, verificationCode);
                Alert alert = new Alert(Alert.AlertType.INFORMATION);
                alert.setTitle("Succès");
                alert.setHeaderText(null);
                alert.setContentText("Un code de vérification a été envoyé à votre email.");
                alert.showAndWait();

                // Show the verification code field
                emailBox.setVisible(false);
                emailButton.setVisible(false);
                verificationCodeContainer.setVisible(true);
                codeButton.setVisible(true);
            } else {
                statusLabel.setText("Aucun compte trouvé avec cet email.");
            }
        } catch (SQLException e) {
            e.printStackTrace();
            statusLabel.setText("Erreur lors de la vérification de l'email.");
        }
    }

    @FXML
    void resetPassword() {
        String code = verificationCodeField.getText().trim();
        String email = emailField.getText().trim();

        // Check if the verification code is entered
        if (code.isEmpty()) {
            statusLabel.setText("Veuillez entrer le code de vérification.");
            return;
        }

        // Validate the verification code
        if (!code.equals(verificationCode)) {
            statusLabel.setText("Le code de vérification est incorrect.");
            return;
        }

        // Prompt the user to enter a new password
        TextInputDialog dialog = new TextInputDialog();
        dialog.setTitle("Réinitialiser le mot de passe");
        dialog.setHeaderText("Entrez votre nouveau mot de passe");
        dialog.setContentText("Nouveau mot de passe:");

        Optional<String> result = dialog.showAndWait();
        if (result.isPresent()) {
            String newPassword = result.get().trim();

            // Validate the new password
            if (newPassword.isEmpty()) {
                statusLabel.setText("Le mot de passe ne peut pas être vide.");
                return;
            }

            if (newPassword.length() < 8) {
                statusLabel.setText("Le mot de passe doit contenir au moins 8 caractères.");
                return;
            }

            if (!newPassword.matches("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).*$")) {
                statusLabel.setText(
                        "Le mot de passe doit contenir au moins une lettre majuscule, une lettre minuscule et un chiffre.");
                return;
            }

            // Update the password in the database
            try {
                User user = userService.getUserByEmail(email);
                if (user != null) {
                    user.setPassword(newPassword);
                    userService.update(user);

                    Alert alert = new Alert(Alert.AlertType.INFORMATION);
                    alert.setTitle("Réinitialisation du mot de passe");
                    alert.setHeaderText(null);
                    alert.setContentText("Votre mot de passe a été réinitialisé avec succès !");
                    alert.showAndWait();
                    goToLogin();
                } else {
                    statusLabel.setText("Aucun utilisateur trouvé avec cet email.");
                }
            } catch (SQLException e) {
                e.printStackTrace();
                statusLabel.setText("Erreur lors de la mise à jour du mot de passe.");
            }
        }
    }

    @FXML
    void goToLogin() {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Auth/Login.fxml"));
            Parent root = loader.load();
            Stage stage = (Stage) emailField.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.setTitle("Login");
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
            statusLabel.setText("Erreur lors du chargement de l'écran <Login>.");
        }
    }

    private String generateVerificationCode() {
        Random random = new Random();
        int code = 100000 + random.nextInt(900000); // Generate a 6-digit code
        return String.valueOf(code);
    }
}