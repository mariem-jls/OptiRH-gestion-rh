package tn.nexus.Services.Recrutement;

import tn.nexus.Entities.Recrutement.Demande;
import tn.nexus.Services.CRUD;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class DemandeService implements CRUD<Demande> {

    private Connection cnx = DBConnection.getInstance().getConnection();
    private Statement st;
    private PreparedStatement ps;

    @Override
    public int insert(Demande demande) throws SQLException {
        // Mise à jour de la requête : suppression de `utilisateur_id`
        String req = "INSERT INTO `demande`(`statut`, `date`, `description`, `offre_id`, `fichier_piece_jointe`, `nom_complet`, `email`, `telephone`, `adresse`, `date_debut_disponible`, `situation_actuelle`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        ps = cnx.prepareStatement(req);
        ps.setString(1, Demande.Statut.EN_ATTENTE.name());
        ps.setTimestamp(2, demande.getDate());
        ps.setString(3, demande.getDescription());
        ps.setInt(4, demande.getOffreId());
        ps.setString(5, demande.getFichierPieceJointe());
        ps.setString(6, demande.getNomComplet());
        ps.setString(7, demande.getEmail());
        ps.setString(8, demande.getTelephone());
        ps.setString(9, demande.getAdresse());
        ps.setDate(10, demande.getDateDebutDisponible());
        ps.setString(11, demande.getSituationActuelle());

        return ps.executeUpdate();
    }

    @Override
    public int update(Demande demande) throws SQLException {
        // Mise à jour de la requête : suppression de `utilisateur_id`
        String req = "UPDATE `demande` SET `statut` = ?, `date` = ?, `description` = ?, `offre_id` = ?, `fichier_piece_jointe` = ?, `nom_complet` = ?, `email` = ?, `telephone` = ?, `adresse` = ?, `date_debut_disponible` = ?, `situation_actuelle` = ? WHERE `id` = ?";

        ps = cnx.prepareStatement(req);
        ps.setString(1, demande.getStatut().name());
        ps.setTimestamp(2, demande.getDate());
        ps.setString(3, demande.getDescription());
        ps.setInt(4, demande.getOffreId());
        ps.setString(5, demande.getFichierPieceJointe());
        ps.setString(6, demande.getNomComplet());
        ps.setString(7, demande.getEmail());
        ps.setString(8, demande.getTelephone());
        ps.setString(9, demande.getAdresse());
        ps.setDate(10, demande.getDateDebutDisponible());
        ps.setString(11, demande.getSituationActuelle());
        ps.setInt(12, demande.getId());

        return ps.executeUpdate();
    }

    @Override
    public int delete(Demande demande) throws SQLException {
        String req = "DELETE FROM `demande` WHERE `id` = ?";

        ps = cnx.prepareStatement(req);
        ps.setInt(1, demande.getId());

        return ps.executeUpdate();
    }

    @Override
    public List<Demande> showAll() throws SQLException {
        List<Demande> temp = new ArrayList<>();
        String req = "SELECT * FROM `demande`";
        st = cnx.createStatement();
        ResultSet rs = st.executeQuery(req);

        while (rs.next()) {
            Demande d = new Demande();
            d.setId(rs.getInt("id"));
            d.setStatut(Demande.Statut.valueOf(rs.getString("statut")));
            d.setDate(rs.getTimestamp("date"));
            d.setDescription(rs.getString("description"));
            // Suppression de `utilisateur_id`
            // d.setUtilisateurId(rs.getInt("utilisateur_id")); // Cette ligne est supprimée
            d.setOffreId(rs.getInt("offre_id"));
            d.setFichierPieceJointe(rs.getString("fichier_piece_jointe"));
            d.setNomComplet(rs.getString("nom_complet"));
            d.setEmail(rs.getString("email"));
            d.setTelephone(rs.getString("telephone"));
            d.setAdresse(rs.getString("adresse"));
            d.setDateDebutDisponible(rs.getDate("date_debut_disponible"));
            d.setSituationActuelle(rs.getString("situation_actuelle"));

            temp.add(d);
        }
        return temp;
    }
    public List<String> getCVsByOffre(int offreId) throws SQLException {
        List<String> cvFiles = new ArrayList<>();
        String req = "SELECT fichier_piece_jointe FROM demande WHERE offre_id = ?";

        ps = cnx.prepareStatement(req);
        ps.setInt(1, offreId);
        ResultSet rs = ps.executeQuery();

        while (rs.next()) {
            String cvPath = rs.getString("fichier_piece_jointe");
            if (cvPath != null && !cvPath.isEmpty()) {
                cvFiles.add(cvPath);
            }
        }
        return cvFiles;
    }
    public Demande getDemandeByCV(String cvFileName) throws SQLException {
        String query = "SELECT * FROM demande WHERE fichier_piece_jointe LIKE ?";
        try (PreparedStatement pstmt = cnx.prepareStatement(query)) {
            pstmt.setString(1, "%" + cvFileName); // Cherche un fichier qui finit par le nom donné
            ResultSet rs = pstmt.executeQuery();

            if (rs.next()) {
                Demande demande = new Demande();
                demande.setId(rs.getInt("id"));
                demande.setStatut(Demande.Statut.valueOf(rs.getString("statut")));
                demande.setDate(rs.getTimestamp("date"));
                demande.setDescription(rs.getString("description"));
                demande.setOffreId(rs.getInt("offre_id"));
                demande.setFichierPieceJointe(rs.getString("fichier_piece_jointe"));
                demande.setNomComplet(rs.getString("nom_complet"));
                demande.setEmail(rs.getString("email"));
                demande.setTelephone(rs.getString("telephone"));
                demande.setAdresse(rs.getString("adresse"));
                demande.setDateDebutDisponible(rs.getDate("date_debut_disponible"));
                demande.setSituationActuelle(rs.getString("situation_actuelle"));
                System.out.println("Recherche demande pour le CV : " + cvFileName);

                return demande;
            }
        }
        return null;
    }


}
