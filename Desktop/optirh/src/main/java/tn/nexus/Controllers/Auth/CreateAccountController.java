package tn.nexus.Controllers.Auth;

import java.io.IOException;
import java.sql.SQLException;

import io.github.palexdev.materialfx.controls.MFXButton;
import io.github.palexdev.materialfx.controls.MFXPasswordField;
import io.github.palexdev.materialfx.controls.MFXTextField;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.control.Label;
import javafx.scene.control.TextField;
import javafx.scene.control.PasswordField;
import javafx.scene.Parent;
import javafx.scene.Scene;
import javafx.scene.control.Alert;
import javafx.scene.control.Button;
import javafx.scene.layout.HBox;
import javafx.stage.Stage;
import tn.nexus.Entities.User;
import tn.nexus.Services.UserService;
import tn.nexus.Services.Auth.MailingService;
import tn.nexus.Utils.Enums.Role;

public class CreateAccountController {

    @FXML
    private MFXTextField nameField;

    @FXML
    private MFXTextField emailField;

    @FXML
    private MFXPasswordField passwordField;

    @FXML
    private MFXPasswordField confirmPasswordField;

    @FXML
    private Label statusLabel;

    @FXML
    private MFXButton createAccountButton;

    @FXML
    private MFXButton goToLoginButton;

    UserService us = new UserService();
    MailingService ms = new MailingService();

    @FXML
    void createAccount() {
        String name = nameField.getText().trim();
        String email = emailField.getText().trim();
        String password = passwordField.getText();
        String confirmPassword = confirmPasswordField.getText();

        if (name.isEmpty() || email.isEmpty() || password.isEmpty() || confirmPassword.isEmpty()) {
            statusLabel.setText("Veuillez remplir tous les champs.");
            return;
        }

        if (!email.matches("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$")) {
            statusLabel.setText("L'email n'est pas valide.");
            return;
        }

        if (password.length() < 8) {
            statusLabel.setText("Le mot de passe doit contenir au moins 8 caractères.");
            return;
        }

        if (!password.matches("^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).*$")) {
            statusLabel.setText(
                    "Le mot de passe doit contenir au moins une lettre majuscule, une lettre minuscule et un chiffre.");
            return;
        }

        if (!password.equals(confirmPassword)) {
            statusLabel.setText("Les mots de passe ne correspondent pas.");
            return;
        }

        try {
            User newUser = new User(name, email, password, Role.Candidat, "");
            us.insert(newUser);
            ms.sendEmail(newUser);
            Alert alert = new Alert(Alert.AlertType.INFORMATION);
            alert.setTitle("Succès");
            alert.setHeaderText(null);
            alert.setContentText("Compte créé avec succès !");
            alert.showAndWait();
            goToLogin();
        } catch (SQLException e) {
            statusLabel.setText("Erreur lors de la création du compte.");
            e.printStackTrace();
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
}