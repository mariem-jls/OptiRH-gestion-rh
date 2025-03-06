package tn.nexus.Controllers.Users;

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
import tn.nexus.Services.Auth.MailingService;
import tn.nexus.Utils.WrapWithSideBar;
import tn.nexus.Utils.Enums.Role;

import java.net.URL;
import java.sql.SQLException;
import java.util.*;

public class UpdateUserController implements Initializable, WrapWithSideBar {
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
    MailingService ms = new MailingService();
    private boolean isUpdate;

    @FXML
    void onSave(ActionEvent event) {
        try {
            if (username.getText() == null || username.getText().isEmpty() || username.getText().length() < 3 || username.getText().length() > 50) {
                throw new InvalidInputException("Le nom d'utilisateur doit contenir entre 3 et 50 caractères");
            } else if (email.getText() == null || email.getText().isEmpty() || !email.getText().matches("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$")) {
                throw new InvalidInputException("L'email n'est pas valide");
            } else if (address.getText() == null || address.getText().isEmpty() || address.getText().length() > 100) {
                throw new InvalidInputException("L'adresse doit contenir moins de 100 caractères");
            } else if (role.getValue() == null) {
                throw new InvalidInputException("Choisir un rôle");
            } else if (!this.isUpdate && (password.getText() == null || password.getText().isEmpty() || password.getText().length() < 8 || password.getText().length() > 100)) {
                throw new InvalidInputException("Le mot de passe doit contenir entre 8 et 100 caractères");
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
                ms.sendEmail(user);
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
}
