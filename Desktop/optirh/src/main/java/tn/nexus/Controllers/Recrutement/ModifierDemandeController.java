package tn.nexus.Controllers.Recrutement;

import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.ComboBox;
import javafx.scene.control.DatePicker;
import javafx.scene.control.TextField;
import javafx.stage.Stage;
import tn.nexus.Entities.Recrutement.Demande;
import tn.nexus.Services.Recrutement.DemandeService;

import java.sql.SQLException;
import java.sql.Timestamp;
import java.time.LocalDate;

public class ModifierDemandeController {

    @FXML private TextField txtDescription;
    @FXML private DatePicker datePicker;
    @FXML private ComboBox<String> comboStatut;
    @FXML private TextField txtNomComplet;
    @FXML private TextField txtEmail;
    @FXML private TextField txtTelephone;
    @FXML private TextField txtAdresse;
    @FXML private DatePicker dateDebutDisponiblePicker;
    @FXML private TextField txtSituationActuelle;

    private DemandeService demandeService = new DemandeService();
    private Demande demande;

    @FXML
    public void initialize() {
        // Remplir la comboBox avec les statuts disponibles
        comboStatut.getItems().setAll(
                Demande.Statut.EN_ATTENTE.toString(),
                Demande.Statut.ACCEPTEE.toString(),
                Demande.Statut.REFUSEE.toString()
        );
        // Désactiver tous les champs sauf le ComboBox statut
        disableFields();
    }
    private void disableFields() {
        txtDescription.setEditable(false);
        datePicker.setDisable(true);
        txtNomComplet.setEditable(false);
        txtEmail.setEditable(false);
        txtTelephone.setEditable(false);
        txtAdresse.setEditable(false);
        dateDebutDisponiblePicker.setDisable(true);
        txtSituationActuelle.setEditable(false);
    }

    public void setDemande(Demande demande) {
        this.demande = demande;
        txtDescription.setText(demande.getDescription());
        datePicker.setValue(demande.getDate().toLocalDateTime().toLocalDate());

        // Remplir les nouveaux champs avec les informations de la demande
        txtNomComplet.setText(demande.getNomComplet());
        txtEmail.setText(demande.getEmail());
        txtTelephone.setText(demande.getTelephone());
        txtAdresse.setText(demande.getAdresse());
        dateDebutDisponiblePicker.setValue(demande.getDateDebutDisponible().toLocalDate());
        txtSituationActuelle.setText(demande.getSituationActuelle());

        // Vérifier que les valeurs sont bien chargées avant de définir la sélection
        if (comboStatut.getItems().isEmpty()) {
            initialize(); // Remplir la ComboBox si ce n'est pas déjà fait
        }

        comboStatut.setValue(demande.getStatut().toString());
    }

    @FXML
    private void modifierDemande() {
        String description = txtDescription.getText().trim();
        LocalDate date = datePicker.getValue();
        String statut = comboStatut.getValue();
        String nomComplet = txtNomComplet.getText().trim();
        String email = txtEmail.getText().trim();
        String telephone = txtTelephone.getText().trim();
        String adresse = txtAdresse.getText().trim();
        LocalDate dateDebutDisponible = dateDebutDisponiblePicker.getValue();
        String situationActuelle = txtSituationActuelle.getText().trim();

        // Validation des champs
        if (description.isEmpty()) {
            showError("La description ne peut pas être vide.");
            return;
        }
        if (description.length() > 500) {
            showError("La description ne peut pas dépasser 500 caractères.");
            return;
        }
        if (date == null || date.isBefore(LocalDate.now())) {
            showError("Veuillez sélectionner une date valide (aujourd'hui ou plus tard).");
            return;
        }
        if (statut == null) {
            showError("Veuillez sélectionner un statut.");
            return;
        }
        if (nomComplet.isEmpty()) {
            showError("Le nom complet ne peut pas être vide.");
            return;
        }
        if (email.isEmpty()) {
            showError("L'email ne peut pas être vide.");
            return;
        }
        if (telephone.isEmpty()) {
            showError("Le téléphone ne peut pas être vide.");
            return;
        }
        if (adresse.isEmpty()) {
            showError("L'adresse ne peut pas être vide.");
            return;
        }
        if (dateDebutDisponible == null) {
            showError("Veuillez sélectionner une date de début disponible.");
            return;
        }
        if (situationActuelle.isEmpty()) {
            showError("La situation actuelle ne peut pas être vide.");
            return;
        }

        // Mise à jour de la demande
        demande.setDescription(description);
        demande.setDate(Timestamp.valueOf(date.atStartOfDay()));
        demande.setStatut(Demande.Statut.valueOf(statut));
        demande.setNomComplet(nomComplet);
        demande.setEmail(email);
        demande.setTelephone(telephone);
        demande.setAdresse(adresse);
        demande.setDateDebutDisponible(java.sql.Date.valueOf(dateDebutDisponible));
        demande.setSituationActuelle(situationActuelle);

        try {
            demandeService.update(demande);
            showSuccess("La demande a été modifiée avec succès.");
            fermerFenetre();
        } catch (SQLException e) {
            showError("Erreur lors de la modification de la demande : " + e.getMessage());
        }
    }

    @FXML
    private void annuler() {
        fermerFenetre();
    }

    private void fermerFenetre() {
        Stage stage = (Stage) txtDescription.getScene().getWindow();
        stage.close();
    }

    private void showError(String message) {
        Alert alert = new Alert(Alert.AlertType.ERROR);
        alert.setTitle("Erreur");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }

    private void showSuccess(String message) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Succès");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}
