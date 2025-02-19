package tn.nexus.Controllers;

import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.fxml.Initializable;
import javafx.scene.layout.AnchorPane;
import javafx.scene.layout.HBox;
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
import io.github.palexdev.materialfx.controls.cell.MFXTableRowCell;
import io.github.palexdev.materialfx.filter.StringFilter;
import tn.nexus.Entities.User;
import tn.nexus.Services.UserService;
import tn.nexus.Utils.WrapWithSideBar;

public class UserController implements Initializable, WrapWithSideBar {

    @FXML
    private AnchorPane sideBar;

    @FXML
    private MFXPaginatedTableView<User> tableView;

    @FXML
    private HBox buttonContainer;

    private UserService userService = new UserService();

    private ObservableList<User> users = FXCollections.observableArrayList();

    @Override
    public void initialize(URL location, ResourceBundle resources) {
        initializeSideBar(sideBar);
        if (tableView == null) {
            throw new IllegalStateException("tableView is not injected: check your FXML file 'ListUsers.fxml'.");
        }
        setupTableColumns();
        setupFilters();
        loadUsers();
        setupPagination();
        setupButtons();
    }

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
    }

    private void setupFilters() {
        tableView.getFilters().addAll(
                new StringFilter<>("Nom", User::getNom),
                new StringFilter<>("Email", User::getEmail),
                new StringFilter<>("Role", User::getRole),
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

        buttonContainer.getChildren().addAll(refreshButton, exportButton);
    }

    private void refreshTable() {
        users.clear();
        loadUsers();
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