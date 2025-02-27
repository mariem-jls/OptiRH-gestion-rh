package tn.nexus.Services.Transport;

import tn.nexus.Entities.transport.Vehicule;
import tn.nexus.Services.CRUD;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

public class VehiculeService implements CRUD<Vehicule> {
    private Connection con = DBConnection.getInstance().getConnection();

    /**
     * Ajouter un véhicule avec un trajet associé.
     * return Nombre de lignes affectées.
     */
    @Override
    public int insert(Vehicule vehicule) throws SQLException {
        String req = "INSERT INTO vehicule (disponibilite, type, nbrPlace, trajet_id) VALUES (?, ?, ?, ?)";
        try (PreparedStatement ps = con.prepareStatement(req, Statement.RETURN_GENERATED_KEYS)) {
            ps.setString(1, vehicule.getDisponibilite());
            ps.setString(2, vehicule.getType());
            ps.setInt(3, vehicule.getNbrplace());
            ps.setInt(4, vehicule.getTrajetId());

            int rowsInserted = ps.executeUpdate();
            if (rowsInserted > 0) {
                try (ResultSet generatedKeys = ps.getGeneratedKeys()) {
                    if (generatedKeys.next()) {
                        vehicule.setId(generatedKeys.getInt(1)); // Récupération de l'ID généré
                    }
                }
            }
            System.out.println("Véhicule ajouté avec succès, ID: " + vehicule.getId());
            return rowsInserted;
        }
    }

    /**
     * Mettre à jour un véhicule.
     * return Nombre de lignes affectées.
     */
    @Override
    public int update(Vehicule vehicule) throws SQLException {
        System.out.println(" Mise à jour du véhicule ID: " + vehicule.getId());

        String req = "UPDATE vehicule SET disponibilite = ?, type = ?, nbrplace = ?, trajet_id = ? WHERE id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setString(1, vehicule.getDisponibilite());
            ps.setString(2, vehicule.getType());
            ps.setInt(3, vehicule.getNbrplace());
            ps.setInt(4, vehicule.getTrajetId());
            ps.setInt(5, vehicule.getId());

            int rowsUpdated = ps.executeUpdate();
            System.out.println(" Nombre de lignes mises à jour: " + rowsUpdated);
            return rowsUpdated;
        }
    }

    /**
     * Supprimer un véhicule.
     * return Nombre de lignes affectées.
     */
    @Override
    public int delete(Vehicule vehicule) throws SQLException {
        String req = "DELETE FROM vehicule WHERE id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setInt(1, vehicule.getId());
            return ps.executeUpdate();
        }
    }

    /**
     * Afficher tous les véhicules.
     * return Liste des véhicules.
     */
    @Override
    public List<Vehicule> showAll() throws SQLException {
        List<Vehicule> vehicules = new ArrayList<>();
        String req = "SELECT * FROM vehicule";

        try (Statement st = con.createStatement();
             ResultSet rs = st.executeQuery(req)) {
            while (rs.next()) {
                Vehicule vehicule = new Vehicule(
                        rs.getInt("id"),
                        rs.getString("disponibilite"),
                        rs.getString("type"),
                        rs.getInt("nbrplace"),
                        rs.getInt("trajet_id")
                );
                vehicules.add(vehicule);
            }
        }
        return vehicules;
    }

    /**
     * Vérifier si un véhicule est disponible.
     * return true si disponible, sinon false.
     */
    public boolean isVehiculeDisponible(int vehiculeId) throws SQLException {
        String req = "SELECT disponibilite FROM vehicule WHERE id = ?";
        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setInt(1, vehiculeId);
            try (ResultSet rs = ps.executeQuery()) {
                if (rs.next()) {
                    return rs.getString("disponibilite").equalsIgnoreCase("Disponible");
                }
            }
        }
        return false;
    }

    /**
     * Récupérer tous les véhicules d'un trajet donné.
     * return Liste des véhicules liés à un trajet spécifique.
     */
    public List<Vehicule> getVehiculesByTrajetId(int trajetId) throws SQLException {
        List<Vehicule> vehicules = new ArrayList<>();
        String req = "SELECT * FROM vehicule WHERE trajet_id = ?";

        try (PreparedStatement ps = con.prepareStatement(req)) {
            ps.setInt(1, trajetId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    vehicules.add(new Vehicule(
                            rs.getInt("id"),
                            rs.getString("disponibilite"),
                            rs.getString("type"),
                            rs.getInt("nbrplace"),
                            rs.getInt("trajet_id")
                    ));
                }
            }
        }
        return vehicules;
    }
}
