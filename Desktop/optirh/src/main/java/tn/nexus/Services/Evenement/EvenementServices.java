package tn.nexus.Services.Evenement;
import tn.nexus.Entities.Evenement.Evenement;
import tn.nexus.Entities.Evenement.StatusEvenement;
import tn.nexus.Services.CRUD;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class EvenementServices implements CRUD<Evenement> {

    private Connection con = DBConnection.getInstance().getConnection();
    private Statement st;
    private PreparedStatement ps;

    @Override
    public int insert(Evenement evenement) throws SQLException {

        // Calculer le statut avant l'insertion
        evenement.calculerStatus();

        // Requête d'insertion
        String req = "INSERT INTO evenement (titre, lieu, description, prix, date_debut, date_fin, image, heure, latitude, longitude, status) " +
                "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        ps = con.prepareStatement(req, Statement.RETURN_GENERATED_KEYS);

        // Remplir les valeurs des placeholders avec les données de l'événement
        ps.setString(1, evenement.getTitre());
        ps.setString(2, evenement.getLieu());
        ps.setString(3, evenement.getDescription());
        ps.setDouble(4, evenement.getPrix());
        ps.setDate(5, Date.valueOf(evenement.getDateDebut()));
        ps.setDate(6, Date.valueOf(evenement.getDateFin()));
        ps.setString(7, evenement.getImage());
        ps.setTime(8, Time.valueOf(evenement.getHeure()));
        ps.setDouble(9, evenement.getLatitude());
        ps.setDouble(10, evenement.getLongitude());
        ps.setString(11, evenement.getStatus().getLabel()); // Ajout de la valeur ENUM correctement formatée

        // Exécuter la requête et récupérer les clés générées
        int rowsAffected = ps.executeUpdate();

        // Si l'insertion a réussi, récupérer l'ID généré
        if (rowsAffected > 0) {
            try (ResultSet generatedKeys = ps.getGeneratedKeys()) {
                if (generatedKeys.next()) {
                    evenement.setIdEvenement(generatedKeys.getInt(1)); // L'ID généré est dans la première colonne
                }
            }
        }

        // Mettre à jour les statuts après l'insertion
        mettreAJourStatutEvenements();

        return rowsAffected;
    }

    @Override
    public int update(Evenement evenement) throws SQLException {
        evenement.calculerStatus();

        String req =  "UPDATE evenement SET titre = ?, lieu = ?, description = ?, prix = ?, date_debut = ?, date_fin = ?, heure = ?, image = ?, latitude = ?, longitude = ?, status = ? " + "WHERE id_evenement = ?";

        try (PreparedStatement ps = con.prepareStatement(req)) {
            // Associer les valeurs des paramètres à l'objet Evenement
            ps.setString(1, evenement.getTitre());
            ps.setString(2, evenement.getLieu());
            ps.setString(3, evenement.getDescription());
            ps.setDouble(4, evenement.getPrix());
            ps.setDate(5, Date.valueOf(evenement.getDateDebut()));  // Convertir LocalDate en Date
            ps.setDate(6, Date.valueOf(evenement.getDateFin()));    // Convertir LocalDate en Date
            ps.setTime(7, Time.valueOf(evenement.getHeure()));      // Convertir LocalTime en Time
            ps.setString(8, evenement.getImage());
            ps.setDouble(9, evenement.getLatitude());
            ps.setDouble(10, evenement.getLongitude());
            ps.setString(11, evenement.getStatus().getLabel()); // Mettre à jour le statut

            ps.setInt(12, evenement.getIdEvenement());  // Utiliser l'ID pour identifier l'événement à mettre à jour

            // Exécuter la mise à jour
            return ps.executeUpdate();  // Retourne le nombre de lignes affectées
        } catch (SQLException e) {
            System.out.println(e.getMessage());
            throw e;
        }
    }

    @Override
    public int delete(Evenement evenement) throws SQLException {
        String req = "DELETE FROM evenement WHERE id_evenement = ?"; // Requête pour supprimer l'événement par id
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setInt(1, evenement.getIdEvenement());
            return ps.executeUpdate(); // Exécute la requête de suppression et retourne le nombre de lignes affectées
        } catch (SQLException e) {
            System.out.println(e.getMessage());
            return 0; // Retourne 0 en cas d'erreur
        }
    }


    @Override
    public List<Evenement> showAll() throws SQLException {

        List<Evenement> events = new ArrayList<>();

        String req = "SELECT * FROM `evenement`";

        // Utilisation de PreparedStatement au lieu de Statement
        ps = con.prepareStatement(req);
        ResultSet rs = ps.executeQuery();

        while (rs.next()) {
            Evenement evenement = new Evenement();
            evenement.setIdEvenement(rs.getInt("id_evenement"));
            evenement.setTitre(rs.getString("titre"));

            // Conversion de la date en LocalDate
            Date dateDebut = rs.getDate("date_debut");
            if (dateDebut != null) {
                evenement.setDateDebut(dateDebut.toLocalDate());
            }

            Date dateFin = rs.getDate("date_fin");
            if (dateFin != null) {
                evenement.setDateFin(dateFin.toLocalDate());
            }

            evenement.setPrix(rs.getDouble("prix"));
            evenement.setDescription(rs.getString("description"));
            evenement.setLieu(rs.getString("lieu"));

            // Conversion de l'heure en LocalTime
            Time heure = rs.getTime("heure");
            if (heure != null) {
                evenement.setHeure(heure.toLocalTime());
            }

            evenement.setImage(rs.getString("image"));
            evenement.setLatitude(rs.getDouble("latitude"));
            evenement.setLongitude(rs.getDouble("longitude"));
            evenement.setStatus(StatusEvenement.fromString(rs.getString("status"))); // ✅ Convertir String → Enum

            events.add(evenement);
        }

        return events;
    }



    public Evenement getEvenementById(int idEvenement) throws SQLException {
        String query = "SELECT * FROM evenement WHERE id_evenement = ?";

        try (PreparedStatement stmt = con.prepareStatement(query)) {
            stmt.setInt(1, idEvenement);
            ResultSet rs = stmt.executeQuery();

            if (rs.next()) {
                Evenement evenement = new Evenement();
                evenement.setIdEvenement(rs.getInt("id_evenement"));
                evenement.setTitre(rs.getString("titre"));
                evenement.setLieu(rs.getString("lieu"));
                evenement.setDescription(rs.getString("description"));
                evenement.setPrix(rs.getDouble("prix"));
                evenement.setDateDebut(rs.getDate("date_debut").toLocalDate());
                evenement.setDateFin(rs.getDate("date_fin").toLocalDate());
                evenement.setImage(rs.getString("image"));
                evenement.setHeure(rs.getTime("heure").toLocalTime());
                evenement.setLatitude(rs.getDouble("latitude"));
                evenement.setLongitude(rs.getDouble("longitude"));
                evenement.setStatus(StatusEvenement.fromString(rs.getString("status")));  // ✅ Convertir String → Enum

                return evenement;
            }
        }

        return null;
    }


    public void mettreAJourStatutEvenements() {
        String sql = "UPDATE evenement SET status = " +
                "CASE " +
                "WHEN date_debut <= NOW() AND date_fin >= NOW() THEN 'en cours' " +
                "WHEN date_fin < NOW() THEN 'terminé' " +
                "ELSE 'à venir' " +
                "END " +
                "WHERE (status != 'en cours' AND date_debut <= NOW() AND date_fin >= NOW()) " +
                "OR (status != 'terminé' AND date_fin < NOW()) " +
                "OR (status != 'à venir' AND date_debut > NOW())";

        try (PreparedStatement statement = con.prepareStatement(sql)) {
            int rowsUpdated = statement.executeUpdate();
            if (rowsUpdated > 0) {
                System.out.println(rowsUpdated + " événements mis à jour !");
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

}
