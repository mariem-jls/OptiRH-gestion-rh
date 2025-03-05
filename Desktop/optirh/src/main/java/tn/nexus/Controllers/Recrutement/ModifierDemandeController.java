package tn.nexus.Controllers.Recrutement;

import javafx.fxml.FXML;
import javafx.scene.control.Alert;
import javafx.scene.control.ComboBox;
import javafx.scene.control.DatePicker;
import javafx.scene.control.TextField;
import javafx.stage.Stage;
import tn.nexus.Entities.Recrutement.Demande;
import tn.nexus.Entities.Recrutement.Offre;
import tn.nexus.Services.EmailService;
import tn.nexus.Services.Recrutement.DemandeService;
import tn.nexus.Services.Recrutement.OffreService;

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
    @FXML private TextField txtPoste;


    private DemandeService demandeService = new DemandeService();
    private EmailService emailService = new EmailService();
    private Demande demande;
    private OffreService offreService = new OffreService();
    private int offreId; // ID de l'offre associée


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
        // 🔥 Récupérer le nom du poste associé
        Offre offre = offreService.getOffreById(demande.getOffreId());
        if (offre != null) {
            System.out.println("Poste associé à la demande : " + offre.getPoste());
            txtPoste.setText(offre.getPoste()); // Assurez-vous que txtPoste est un champ défini dans le FXML
        } else {
            System.out.println("Aucune offre trouvée pour cette demande.");
        }
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
            // Vérifier si le statut a été changé et envoyer un email en fonction du statut
            if (statut.equals(Demande.Statut.ACCEPTEE.toString())) {
                // Envoi de l'email pour une demande acceptée
                emailService.sendAcceptedEmail(demande.getEmail(), demande.getNomComplet(), "Nom du Poste"); // Remplacez "Nom du Poste" par l'offre associée
            } else if (statut.equals(Demande.Statut.REFUSEE.toString())) {
                // Envoi de l'email pour une demande refusée
                emailService.sendRejectedEmail(demande.getEmail(), demande.getNomComplet(), "Nom du Poste"); // Remplacez "Nom du Poste" par l'offre associée
            }
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
