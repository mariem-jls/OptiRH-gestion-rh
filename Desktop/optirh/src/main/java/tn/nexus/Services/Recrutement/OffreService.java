package tn.nexus.Services.Recrutement;

import tn.nexus.Entities.Recrutement.Offre;
import tn.nexus.Services.CRUD;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class OffreService implements CRUD<Offre> {

    private Connection cnx = DBConnection.getInstance().getConnection();
    private PreparedStatement ps;
    private Statement st;

    @Override
    public int insert(Offre offre) throws SQLException {
        String req = "INSERT INTO `offre`(`poste`, `description`, `statut`, `date_creation`, `mode_travail`, `type_contrat`, `localisation`, `niveau_experience`, `nb_postes`, `date_expiration`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        ps = cnx.prepareStatement(req);

        ps.setString(1, offre.getPoste());
        ps.setString(2, offre.getDescription());
        ps.setString(3, offre.getStatut());
        ps.setTimestamp(4, Timestamp.valueOf(offre.getDateCreation()));  // Date de création

        ps.setString(5, offre.getModeTravail());
        ps.setString(6, offre.getTypeContrat());
        ps.setString(7, offre.getLocalisation());
        ps.setString(8, offre.getNiveauExperience());
        ps.setInt(9, offre.getNbPostes());
        ps.setTimestamp(10, Timestamp.valueOf(offre.getDateExpiration()));

        return ps.executeUpdate();
    }

    @Override
    public int update(Offre offre) throws SQLException {
        String req = "UPDATE `offre` SET `poste` = ?, `description` = ?, `statut` = ?, `date_creation` = ?, `mode_travail` = ?, `type_contrat` = ?, `localisation` = ?, `niveau_experience` = ?, `nb_postes` = ?, `date_expiration` = ? WHERE `id` = ?";
        ps = cnx.prepareStatement(req);

        ps.setString(1, offre.getPoste());
        ps.setString(2, offre.getDescription());
        ps.setString(3, offre.getStatut());
        ps.setTimestamp(4, Timestamp.valueOf(offre.getDateCreation()));

        ps.setString(5, offre.getModeTravail());
        ps.setString(6, offre.getTypeContrat());
        ps.setString(7, offre.getLocalisation());
        ps.setString(8, offre.getNiveauExperience());
        ps.setInt(9, offre.getNbPostes());
        ps.setTimestamp(10, Timestamp.valueOf(offre.getDateExpiration()));

        ps.setInt(11, offre.getId());

        return ps.executeUpdate();
    }

    @Override
    public int delete(Offre offre) throws SQLException {
        String req = "DELETE FROM `offre` WHERE `id` = ?";
        ps = cnx.prepareStatement(req);
        ps.setInt(1, offre.getId());

        return ps.executeUpdate();
    }

    @Override
    public List<Offre> showAll() throws SQLException {
        List<Offre> offres = new ArrayList<>();
        String req = "SELECT * FROM offre";
        st = cnx.createStatement();
        ResultSet rs = st.executeQuery(req);

        while (rs.next()) {
            Offre offre = new Offre();
            offre.setId(rs.getInt("id"));
            offre.setPoste(rs.getString("poste"));
            offre.setDescription(rs.getString("description"));
            offre.setStatut(rs.getString("statut"));

            Timestamp timestamp = rs.getTimestamp("date_creation");
            if (timestamp != null) {
                offre.setDateCreation(timestamp.toLocalDateTime());
            }

            offre.setModeTravail(rs.getString("mode_travail"));
            offre.setTypeContrat(rs.getString("type_contrat"));
            offre.setLocalisation(rs.getString("localisation"));
            offre.setNiveauExperience(rs.getString("niveau_experience"));
            offre.setNbPostes(rs.getInt("nb_postes"));

            Timestamp dateExp = rs.getTimestamp("date_expiration");
            if (dateExp != null) {
                offre.setDateExpiration(dateExp.toLocalDateTime());
            }

            offres.add(offre);
        }
        return offres;
    }

    public List<Offre> getAllOffres(String searchQuery) {
        // La requête SQL pour récupérer les offres filtrées
        String query = "SELECT * FROM offre WHERE poste LIKE ? OR localisation LIKE ?";
        try (PreparedStatement stmt = cnx.prepareStatement(query)) {
            // Paramétrer la recherche pour le poste et la localisation
            stmt.setString(1, "%" + searchQuery + "%");
            stmt.setString(2, "%" + searchQuery + "%");

            // Exécuter la requête et récupérer les résultats
            ResultSet rs = stmt.executeQuery();
            List<Offre> offres = new ArrayList<>();

            // Parcourir les résultats
            while (rs.next()) {
                // Récupérer les données depuis le ResultSet et les mapper dans une instance de Offre
                Offre offre = new Offre(
                        rs.getInt("id"),
                        rs.getString("poste"),
                        rs.getString("description"),         // Ajout de description
                        rs.getString("statut"),              // Ajout de statut
                        rs.getTimestamp("date_creation").toLocalDateTime(),  // Conversion en LocalDateTime
                        rs.getString("mode_travail"),        // Ajout de mode de travail
                        rs.getString("type_contrat"),        // Ajout de type de contrat
                        rs.getString("localisation"),
                        rs.getString("niveau_experience"),   // Ajout du niveau d'expérience
                        rs.getInt("nb_postes"),
                        rs.getTimestamp("date_expiration").toLocalDateTime()  // Conversion en LocalDateTime
                );
                offres.add(offre);
            }
            return offres;
        } catch (SQLException e) {
            e.printStackTrace();
            return new ArrayList<>();
        }
    }


    // Méthode pour récupérer les offres paginées et filtrées par recherche
    public List<Offre> getOffresPaginated(int page, int itemsPerPage, String searchQuery) throws SQLException {
        List<Offre> offres = new ArrayList<>();

        String query = "SELECT * FROM offre WHERE poste LIKE ? ORDER BY date_creation DESC LIMIT ? OFFSET ?";

            try (PreparedStatement stmt = cnx.prepareStatement(query)) {
                // Préparer les paramètres
                stmt.setString(1, "%" + searchQuery + "%");
                stmt.setInt(2, itemsPerPage);  // LIMIT
                stmt.setInt(3, (page - 1) * itemsPerPage); // OFFSET


                try (ResultSet rs = stmt.executeQuery()) {
                    while (rs.next()) {
                        Offre offre = new Offre();
                        offre.setId(rs.getInt("id"));
                        offre.setPoste(rs.getString("poste"));
                        offre.setDescription(rs.getString("description"));
                        offre.setModeTravail(rs.getString("mode_travail"));
                        offre.setTypeContrat(rs.getString("type_contrat"));
                        offre.setLocalisation(rs.getString("localisation"));
                        offre.setNiveauExperience(rs.getString("niveau_experience"));
                        offre.setNbPostes(rs.getInt("nb_postes"));
                        offre.setStatut(rs.getString("statut"));
                        offre.setDateCreation(rs.getTimestamp("date_creation").toLocalDateTime());
                        offre.setDateExpiration(rs.getTimestamp("date_expiration") != null ? rs.getTimestamp("date_expiration").toLocalDateTime() : null);

                        offres.add(offre);
                        System.out.println("Nombre d'offres récupérées: " + offres.size());

                    }
                }
            }


        return offres;
    }
    public List<Offre> getOffresActives() throws SQLException {
        List<Offre> offres = new ArrayList<>();
        String req = "SELECT * FROM offre WHERE statut = 'Active'";
        st = cnx.createStatement();
        ResultSet rs = st.executeQuery(req);

        while (rs.next()) {
            Offre offre = new Offre();
            offre.setId(rs.getInt("id"));
            offre.setPoste(rs.getString("poste"));
            offre.setDescription(rs.getString("description"));
            offre.setStatut(rs.getString("statut"));

            // Gestion des dates avec vérification de null
            Timestamp dateCreation = rs.getTimestamp("date_creation");
            if (dateCreation != null) {
                offre.setDateCreation(dateCreation.toLocalDateTime());
            }

            offre.setModeTravail(rs.getString("mode_travail"));
            offre.setTypeContrat(rs.getString("type_contrat"));
            offre.setLocalisation(rs.getString("localisation"));
            offre.setNiveauExperience(rs.getString("niveau_experience"));
            offre.setNbPostes(rs.getInt("nb_postes"));

            Timestamp dateExpiration = rs.getTimestamp("date_expiration");
            if (dateExpiration != null) {
                offre.setDateExpiration(dateExpiration.toLocalDateTime());
            }

            offres.add(offre);
        }
        return offres;
    }
}

