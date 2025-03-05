package tn.nexus.Controllers.Users;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.fxml.FXMLLoader;
import javafx.fxml.Initializable;
import javafx.scene.Parent;
import javafx.scene.control.Button;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import javafx.stage.FileChooser;

import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.net.URL;
import java.sql.SQLException;
import java.util.Comparator;
import java.util.ResourceBundle;

import de.jensd.fx.glyphs.fontawesome.FontAwesomeIcon;
import de.jensd.fx.glyphs.fontawesome.FontAwesomeIconView;
import io.github.palexdev.materialfx.controls.MFXButton;
import io.github.palexdev.materialfx.controls.MFXPaginatedTableView;
import io.github.palexdev.materialfx.controls.MFXTableColumn;
import io.github.palexdev.materialfx.controls.MFXTextField;
import io.github.palexdev.materialfx.controls.cell.MFXTableRowCell;
import io.github.palexdev.materialfx.filter.EnumFilter;
import io.github.palexdev.materialfx.filter.StringFilter;
import tn.nexus.Entities.User;
import tn.nexus.Services.UserService;
import tn.nexus.Utils.WrapWithSideBar;
import tn.nexus.Utils.Enums.Role;

public class UserController implements Initializable, WrapWithSideBar {

    @FXML
    private VBox userDetailsPanel;
    @FXML
    private MFXTextField nomField, emailField, addressField, passwordField;
    @FXML
    private MFXButton updateButton, deleteButton, addButton;
    @FXML
    private Button addUserButton;
    @FXML
    private AnchorPane sideBar;
    @FXML
    private MFXPaginatedTableView<User> tableView;
    @FXML
    private HBox buttonContainer;
    @FXML
    private VBox userPanel;
    private UserService userService = new UserService();
    private ObservableList<User> users = FXCollections.observableArrayList();
    private User selectedUser;

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        initializeSideBar(sideBar);
        if (tableView == null) {
            throw new IllegalStateException("tableView is not injected: check your FXML file 'ListUsers.fxml'.");
        }
        setupTableColumns();
        setupFilters();
        setupPagination();
        setupButtons();
        loadUsers();
        tableView.getSelectionModel().selectionProperty().addListener((obs, oldSelection, newSelection) -> {
            if (newSelection != null && !newSelection.isEmpty()) {
                selectedUser = tableView.getSelectionModel().getSelectedValues().get(0);
                showUserDetails(selectedUser, true);
            }
        });
    }

    private void showUserDetails(User user, boolean isUpdate) {
        boolean isPresent = isUpdate;
        try {
            FXMLLoader loader = new FXMLLoader(getClass().getResource("/Users/UserDetails.fxml"));
            Parent root = loader.load();
            UpdateUserController controller = loader.getController();
            controller.setPageTitle(isPresent ? "Modifier Utilisateur" : "Ajouter Utilisateur");
            controller.setUser(user);
            controller.setUpdate(isPresent);
            userPanel.getScene().setRoot(root);
        } catch (Exception e) {
            throw new RuntimeException(e);
        }
    }

    // private void clearUserDetails() {
    // nomField.clear();
    // emailField.clear();
    // roleField.getSelectionModel().clearSelection();
    // addressField.clear();
    // passwordField.clear();
    // userDetailsPanel.setVisible(false);
    // }

    // private void initializeEmptyUserDetails() {

    // roleField.getItems().setAll(Role.values());
    // userDetailsPanel.setVisible(true);
    // }

    private void setupTableColumns() {
        MFXTableColumn<User> nomColumn = new MFXTableColumn<>("Nom", true, Comparator.comparing(User::getNom));
        MFXTableColumn<User> emailColumn = new MFXTableColumn<>("Email", true, Comparator.comparing(User::getEmail));
        MFXTableColumn<User> roleColumn = new MFXTableColumn<>("Role", true, Comparator.comparing(User::getRole));
        MFXTableColumn<User> addressColumn = new MFXTableColumn<>("Address", true,
                Comparator.comparing(User::getAddress));
        nomColumn.setRowCellFactory(user -> new MFXTableRowCell<>(User::getNom));
        emailColumn.setRowCellFactory(user -> new MFXTableRowCell<>(User::getEmail));
        roleColumn.setRowCellFactory(user -> new MFXTableRowCell<>(User::getRole));
        addressColumn.setRowCellFactory(user -> new MFXTableRowCell<>(User::getAddress));
        tableView.getTableColumns().addAll(nomColumn, emailColumn, roleColumn, addressColumn);
        tableView.autosizeColumnsOnInitialization();
        // userDetailsPanel.setVisible(false);

        // updateButton.setOnAction(event -> {
        // if (selectedUser != null) {
        // selectedUser.setNom(nomField.getText());
        // selectedUser.setEmail(emailField.getText());
        // selectedUser.setRole(roleField.getValue());
        // selectedUser.setAddress(addressField.getText());

        // try {
        // userService.update(selectedUser);
        // refreshTable();
        // Alert alert = new Alert(Alert.AlertType.INFORMATION);
        // alert.setTitle("Succès de la mise à jour");
        // alert.setHeaderText(null);
        // alert.setContentText("Utilisateur mis à jour avec succès !");
        // alert.showAndWait();
        // } catch (SQLException e) {
        // Alert alert = new Alert(Alert.AlertType.ERROR);
        // alert.setTitle("Erreur de mise à jour");
        // alert.setHeaderText("Erreur lors de la mise à jour de l'utilisateur");
        // alert.setContentText(e.getMessage());
        // alert.showAndWait();
        // }
        // }
        // });

        // deleteButton.setOnAction(event -> {
        // if (selectedUser != null) {
        // try {
        // userService.delete(selectedUser);
        // refreshTable();
        // selectedUser = null;
        // clearUserDetails();
        // userDetailsPanel.setVisible(false);

        // Alert alert = new Alert(Alert.AlertType.INFORMATION);
        // alert.setTitle("Suppression réussie");
        // alert.setHeaderText(null);
        // alert.setContentText("Utilisateur supprimé avec succès");
        // alert.showAndWait();
        // } catch (SQLException e) {
        // Alert alert = new Alert(Alert.AlertType.ERROR);
        // alert.setTitle("Erreur de suppression");
        // alert.setHeaderText("Erreur lors de la suppression de l'utilisateur");
        // alert.setContentText(e.getMessage());
        // alert.showAndWait();
        // }
        // }
        // });

        // addButton.setOnAction(event -> {
        // User user = new User();
        // user.setNom(nomField.getText());
        // user.setEmail(emailField.getText());
        // user.setRole(roleField.getValue());
        // user.setMotDePasse(passwordField.getText());
        // user.setAddress(addressField.getText());

        // try {
        // userService.insert(user);
        // refreshTable();

        // Alert alert = new Alert(Alert.AlertType.INFORMATION);
        // alert.setTitle("Insertion réussie");
        // alert.setHeaderText(null);
        // alert.setContentText("Utilisateur " + nomField.getText() + " ajouté avec
        // succès");
        // alert.showAndWait();
        // } catch (SQLException e) {
        // Alert alert = new Alert(Alert.AlertType.ERROR);
        // alert.setTitle("Erreur d'insertion");
        // alert.setHeaderText("Erreur lors de l'insertion de l'utilisateur");
        // alert.setContentText(e.getMessage());
        // alert.showAndWait();
        // }
        // });
        addUserButton.setOnAction(event -> {
            showUserDetails(new User(), false);
        });
    }

    private void setupFilters() {
        tableView.getFilters().addAll(
                new StringFilter<>("Nom", User::getNom),
                new StringFilter<>("Email", User::getEmail),
                new EnumFilter<>("Role", User::getRole, Role.class),
                new StringFilter<>("Address", User::getAddress));
    }

    private void loadUsers() {
        try {
            users.addAll(userService.showAll());
        } catch (SQLException e) {
            e.printStackTrace();
        }
        tableView.setItems(users);
    }

    private void setupPagination() {
        tableView.setRowsPerPage(16);
    }

    private void setupButtons() {
        MFXButton refreshButton = new MFXButton("Actualiser");
        refreshButton.getStyleClass().add("fancy-button");
        refreshButton.setStyle("-fx-background-color: #4CAF50; -fx-text-fill: white;");
        FontAwesomeIconView refreshIcon = new FontAwesomeIconView(FontAwesomeIcon.REFRESH);
        refreshIcon.setFill(javafx.scene.paint.Color.WHITE);
        refreshButton.setGraphic(refreshIcon);
        refreshButton.setOnAction(event -> refreshTable());
        MFXButton exportButton = new MFXButton("Exporter CSV");
        exportButton.getStyleClass().add("fancy-button");
        exportButton.setStyle("-fx-background-color: #2196F3; -fx-text-fill: white;");
        FontAwesomeIconView exportIcon = new FontAwesomeIconView(FontAwesomeIcon.DOWNLOAD);
        exportIcon.setFill(javafx.scene.paint.Color.WHITE);
        exportButton.setGraphic(exportIcon);
        exportButton.setOnAction(event -> exportToCSV());
        refreshButton.setPrefWidth(150);
        exportButton.setPrefWidth(150);
        VBox buttonContainer = new VBox(1);
        buttonContainer.getChildren().addAll(refreshButton, exportButton);
        this.buttonContainer.getChildren().add(buttonContainer);
    }

    private void refreshTable() {
        try {
            users.setAll(userService.showAll());
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    private void exportToCSV() {
        FileChooser fileChooser = new FileChooser();
        fileChooser.setTitle("Entregistrez le fichier CSV");
        fileChooser.getExtensionFilters().add(new FileChooser.ExtensionFilter("CSV Files", "*.csv"));
        File file = fileChooser.showSaveDialog(tableView.getScene().getWindow());
        if (file != null) {
            try (FileWriter writer = new FileWriter(file)) {
                writer.write("ID,Nom,Email,Role,Address\n");
                for (User user : users) {
                    writer.write(user.getId() + "," + user.getNom() + "," + user.getEmail() + "," + user.getRole() + ","
                            + user.getAddress() + "\n");
                }
                writer.flush();
            } catch (IOException e) {
                e.printStackTrace();
            }
        }
    }
}
