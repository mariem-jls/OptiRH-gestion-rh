package tn.nexus.Controllers.Auth;

import java.io.IOException;

import io.github.palexdev.materialfx.controls.MFXPasswordField;
import io.github.palexdev.materialfx.controls.MFXTextField;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.scene.Scene;
import javafx.scene.control.Label;
import javafx.stage.Stage;
import tn.nexus.Entities.User;
import tn.nexus.Services.Auth.AuthService;
import javafx.scene.Parent;

public class LoginController {
    @FXML
    private MFXTextField emailField;

    @FXML
    private MFXPasswordField passwordField;

    @FXML
    private Label statusLabel;

    @FXML
    private void login(ActionEvent event) {
        String email = emailField.getText();
        String password = passwordField.getText();

        User user = AuthService.loginUser(email, password);

        if (user != null) {
            statusLabel.setText("Login successful!");
            System.out.println("Logged in as: " + user.getNom() + " (" + user.getRole() + ")");

            // Redirect to ListUsers.fxml
            try {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/Users/ListUsers.fxml"));
                Parent root = loader.load();

                // Get the current stage and set the new scene
                Stage stage = (Stage) emailField.getScene().getWindow();
                stage.setScene(new Scene(root));
                stage.setTitle("User List");
                stage.show();
            } catch (Exception e) {
                e.getMessage();
                statusLabel.setText("Erreur lors de chargement de la liste des utilisateurs.");
            }
        } else {
            statusLabel.setText("Identifiants invalides.");
        }
    }

    public void forgotPassword(ActionEvent event) {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Auth/ForgotPassword.fxml"));
            Parent root = loader.load();
            Stage stage = (Stage) emailField.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.setTitle("Mot de passe oublié");
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
            statusLabel.setText("Erreur lors du chargement de l'ecran <Mot de passe oublié>");
        }
    }

    public void createAccount(ActionEvent event) {
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Auth/CreateAccount.fxml"));
            Parent root = loader.load();
            Stage stage = (Stage) emailField.getScene().getWindow();
            stage.setScene(new Scene(root));
            stage.setTitle("Créer un compte");
            stage.show();
        } catch (IOException e) {
            e.printStackTrace();
            statusLabel.setText("Erreur lors du chargement de l'écran <Créer un compte>.");
        }
    }

}
