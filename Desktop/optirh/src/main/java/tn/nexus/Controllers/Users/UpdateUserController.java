package tn.nexus.Controllers.Users;

import io.github.cdimascio.dotenv.Dotenv;
import io.github.palexdev.materialfx.controls.MFXButton;
import io.github.palexdev.materialfx.controls.MFXPasswordField;
import io.github.palexdev.materialfx.controls.MFXTextField;
import io.github.palexdev.materialfx.controls.legacy.MFXLegacyComboBox;
import javafx.event.ActionEvent;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.fxml.Initializable;
import javafx.scene.Parent;
import javafx.scene.control.Alert;
import javafx.scene.layout.AnchorPane;
import javafx.scene.text.Text;
import tn.nexus.Entities.User;
import tn.nexus.Exceptions.InvalidInputException;
import tn.nexus.Services.UserService;
import tn.nexus.Utils.WrapWithSideBar;
import tn.nexus.Utils.Enums.Role;

import java.net.URL;
import java.sql.SQLException;
import java.util.*;

import org.simplejavamail.api.email.Email;
import org.simplejavamail.api.mailer.Mailer;
import org.simplejavamail.api.mailer.config.TransportStrategy;
import org.simplejavamail.email.EmailBuilder;
import org.simplejavamail.mailer.MailerBuilder;

public class UpdateUserController implements Initializable, WrapWithSideBar {

    Dotenv dotenv = Dotenv.configure().ignoreIfMissing().load();
    private final String MAIL_ADDRESS = dotenv.get("MAIL_ADDRESS");
    private final String MAIL_PASSWORD = dotenv.get("MAIL_PASSWORD");
    private final int MAIL_PORT = Integer.parseInt(dotenv.get("MAIL_PORT"));
    private final String MAIL_HOST = dotenv.get("MAIL_HOST");

    private User user;
    @FXML
    private AnchorPane sideBar;
    @FXML
    private MFXButton cancelBtn;
    @FXML
    private MFXTextField email;
    @FXML
    private MFXLegacyComboBox<Role> role;
    @FXML
    private MFXButton saveBtn;
    @FXML
    private MFXTextField username;
    @FXML
    private MFXPasswordField password;
    @FXML
    private MFXTextField address;
    @FXML
    private Text pageTitle;
    UserService us = new UserService();
    private boolean isUpdate;

    @FXML
    void onSave(ActionEvent event) {
        try {
            if (username.getText() == null || username.getText().isEmpty()) {
                throw new InvalidInputException("Le nom d'utilisateur est requis");
            } else if (email.getText() == null || email.getText().isEmpty()) {
                throw new InvalidInputException("L'email est requis");
            } else if (address.getText() == null || address.getText().isEmpty()) {
                throw new InvalidInputException("L'adresse est requise");
            } else if (role.getValue() == null) {
                throw new InvalidInputException("Choisir un rôle");
            } else if (!this.isUpdate && (password.getText() == null || password.getText().isEmpty())) {
                throw new InvalidInputException("Le mot de passe est requis");
            }
            user.setNom(username.getText());
            user.setMotDePasse(password.getText());
            user.setEmail(email.getText());
            user.setAddress(address.getText());
            user.setRole(role.getValue());

            if (this.isUpdate)
                us.update(user);
            else {
                us.insert(user);
                sendEmail(user);
            }

            Alert alert = new Alert(Alert.AlertType.INFORMATION);
            alert.setTitle("Succès");
            alert.setHeaderText(null);
            if (this.isUpdate)
                alert.setContentText("Utilisateur modifié avec succès");
            else
                alert.setContentText("Utilisateur ajouté avec succès");
            alert.showAndWait();

            // redirect to list
            Parent root = null;
            try {
                FXMLLoader loader = new FXMLLoader(getClass().getResource("/users/ListUsers.fxml"));
                root = loader.load();
            } catch (Exception e) {
                throw new Exception(e.getMessage());
            }
            username.getScene().setRoot(root);
        } catch (InvalidInputException e) {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Erreur");
            alert.setHeaderText(null);
            alert.setContentText(e.getMessage());
            alert.showAndWait();
        } catch (Exception e) {
            System.out.println(e.getStackTrace());
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Erreur");
            alert.setHeaderText(null);
            alert.setContentText("An error has occured");
            alert.showAndWait();
        }
    }

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        initializeSideBar(sideBar);
        List<Role> roles = Arrays.asList(Role.values());
        role.getItems().addAll(roles);
    }

    public void setUser(User user) throws SQLException {
        this.user = user;
        // Initialize the form with the user data
        username.setText(user.getNom());
        password.setText(user.getMotDePasse());
        email.setText(user.getEmail());
        address.setText(user.getAddress());
        role.setValue(user.getRole());
    }

    public void setUpdate(boolean isUpdate) {
        this.isUpdate = isUpdate;
    }

    @FXML
    void OnClickCancelBtn() {
        Parent root = null;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/users/ListUsers.fxml"));
            root = loader.load();
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
        username.getScene().setRoot(root);
    }

    public void setPageTitle(String title) {
        this.pageTitle.setText(title);
    }

    private void sendEmail(User user) {
        try {
            // Configuration du serveur SMTP (exemple avec Gmail)
            Mailer mailer = MailerBuilder
                    .withSMTPServer(MAIL_HOST, MAIL_PORT, MAIL_ADDRESS, MAIL_PASSWORD)
                    .withTransportStrategy(TransportStrategy.SMTP_TLS)
                    .buildMailer();

            // Création de l'email
            Email email = EmailBuilder.startingBlank()
                    .from(MAIL_ADDRESS)
                    .to(user.getEmail())
                    .withSubject("Bienvenue " + user.getNom())
                    .withHTMLText("<html>" +
                            "<head>" +
                            "<style>" +
                            "body { font-family: Arial, sans-serif; line-height: 1.6; }" +
                            ".header { text-align: center; }" +
                            ".logo { width: 150px; }" +
                            ".content { margin: 20px; }" +
                            ".footer { margin-top: 30px; font-size: small; color: gray; }" +
                            "</style>" +
                            "</head>" +
                            "<body>" +
                            "<div class='header'>" +
                            "<img src='https://i.ibb.co/0yTLr7bq/408065252-0f3bdb15-6321-42da-b294-c12b76d025d3.png' class='logo' alt='Company Logo' />"
                            +
                            "<h1>Bienvenue sur notre plateforme!</h1>" +
                            "</div>" +
                            "<div class='content'>" +
                            "<p>Bonjour " + user.getNom() + ",</p>" +
                            "<p>Bienvenue sur notre plateforme. Votre compte a été créé avec succès.</p>" +
                            "<p>Vous avez le rôle suivant : <strong>" + user.getRole() + "</strong>.</p>" +
                            "<p>Nous vous remercions de votre confiance.</p>" +
                            "</div>" +
                            "<div class='footer'>" +
                            "<p>Cordialement,</p>" +
                            "<p>L'équipe OptiRH</p>" +
                            "</div>" +
                            "</body>" +
                            "</html>")
                    .buildEmail();

            System.out.println("Email envoyé avec succès à " + user.getEmail());
            // Envoi de l'email
            mailer.sendMail(email);
        } catch (Exception e) {
            Alert alert = new Alert(Alert.AlertType.ERROR);
            alert.setTitle("Erreur d'envoi d'email");
            alert.setHeaderText(null);
            alert.setContentText("L'email n'a pas pu être envoyé : " + e.getMessage());
            alert.showAndWait();
        }
    }
}
