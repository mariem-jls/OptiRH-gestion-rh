package tn.nexus.Services.Transport;

import tn.nexus.Entities.User;
import tn.nexus.Entities.transport.ReservationTrajet;
import tn.nexus.Entities.transport.Trajet;
import tn.nexus.Entities.transport.Vehicule;
import tn.nexus.Services.UserService;
import tn.nexus.Utils.DBConnection;

import java.sql.*;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class ReservationTrajetService implements tn.nexus.Services.CRUD<ReservationTrajet> {
    private Connection connection;
    private MailService mailService;


    public ReservationTrajetService() {
        this.connection = DBConnection.getInstance().getConnection(); // Utilisation du Singleton
        this.mailService = new MailService(); // Initialisation du service d'e-mail

    }

    @Override
    public int insert(ReservationTrajet reservation) throws SQLException {
        // Contrôle de saisie
        if (reservation.getDisponibilite() == null || reservation.getDisponibilite().isEmpty()) {
            throw new IllegalArgumentException("La disponibilité ne peut pas être vide.");
        }
        if (reservation.getVehiculeId() <= 0) {
            throw new IllegalArgumentException("L'ID du véhicule doit être positif.");
        }
        if (reservation.getTrajetId() <= 0) {
            throw new IllegalArgumentException("L'ID du trajet doit être positif.");
        }
        if (reservation.getUserId() <= 0) {
            throw new IllegalArgumentException("L'ID de l'utilisateur doit être positif.");
        }

        // Vérifier si le véhicule, le trajet et l'utilisateur existent
        if (!vehiculeExists(reservation.getVehiculeId())) {
            throw new IllegalArgumentException("Le véhicule spécifié n'existe pas.");
        }
        if (!trajetExists(reservation.getTrajetId())) {
            throw new IllegalArgumentException("Le trajet spécifié n'existe pas.");
        }
        if (!userExists(reservation.getUserId())) {
            throw new IllegalArgumentException("L'utilisateur spécifié n'existe pas.");
        }

        // Requête SQL pour insérer une réservation
        String query = "INSERT INTO reservationtrajet (disponibilite, utilisateur_id, trajet_id, vehicule_id) VALUES (?, ?, ?, ?)";
        try (PreparedStatement statement = connection.prepareStatement(query, Statement.RETURN_GENERATED_KEYS)) {
            statement.setString(1, reservation.getDisponibilite());
            statement.setInt(2, reservation.getUserId());
            statement.setInt(3, reservation.getTrajetId());
            statement.setInt(4, reservation.getVehiculeId());


            int rowsInserted = statement.executeUpdate();
            if (rowsInserted > 0) {
                try (ResultSet generatedKeys = statement.getGeneratedKeys()) {
                    if (generatedKeys.next()) {
                        int reservationId = generatedKeys.getInt(1); // Retourne l'ID généré




                        // Récupérer les détails du trajet, du véhicule et de l'utilisateur
                        TrajetService trajetService = new TrajetService();
                        VehiculeService vehiculeService = new VehiculeService();
                        UserService userService = new UserService();

                        Trajet trajet = trajetService.getTrajetById(reservation.getTrajetId());
                        Vehicule vehicule = vehiculeService.getVehiculeById(reservation.getVehiculeId());
                        User user = userService.getUserById2(reservation.getUserId());

                        // Envoyer un e-mail de confirmation
                        mailService.sendReservationConfirmation(
                                user.getEmail(), // E-mail de l'utilisateur
                                vehicule.getType(), // Type de véhicule
                                trajet.getDepart(), // Point de départ
                                trajet.getArrive(), // Point d'arrivée
                                trajet.getStation() // Station
                        );

                        return reservationId; // Retourner l'ID généré
                    }
                }
            }


        }
        return -1; // Échec de l'insertion
    }




    @Override
    public int update(ReservationTrajet reservation) throws SQLException {
        // Contrôle de saisie
        if (reservation.getDisponibilite() == null || reservation.getDisponibilite().isEmpty()) {
            throw new IllegalArgumentException("La disponibilité ne peut pas être vide.");
        }
        if (reservation.getVehiculeId() <= 0) {
            throw new IllegalArgumentException("L'ID du véhicule doit être positif.");
        }
        if (reservation.getTrajetId() <= 0) {
            throw new IllegalArgumentException("L'ID du trajet doit être positif.");
        }
        if (reservation.getUserId() <= 0) {
            throw new IllegalArgumentException("L'ID de l'utilisateur doit être positif.");
        }

        // Vérifier si le véhicule, le trajet et l'utilisateur existent
        if (!vehiculeExists(reservation.getVehiculeId())) {
            throw new IllegalArgumentException("Le véhicule spécifié n'existe pas.");
        }
        if (!trajetExists(reservation.getTrajetId())) {
            throw new IllegalArgumentException("Le trajet spécifié n'existe pas.");
        }
        if (!userExists(reservation.getUserId())) {
            throw new IllegalArgumentException("L'utilisateur spécifié n'existe pas.");
        }

        String query = "UPDATE reservationtrajet SET disponibilite = ?, utilisateur_id = ?, vehicule_id = ?, trajet_id = ? WHERE id = ?";
        try (PreparedStatement statement = connection.prepareStatement(query)) {
            statement.setString(1, reservation.getDisponibilite());
            statement.setInt(2, reservation.getUserId());
            statement.setInt(3, reservation.getVehiculeId());
            statement.setInt(4, reservation.getTrajetId());
            statement.setInt(5, reservation.getId());

            return statement.executeUpdate();
        }
    }

    @Override
    public int delete(ReservationTrajet reservation) throws SQLException {
        String query = "DELETE FROM reservationtrajet WHERE id = ?";
        try (PreparedStatement statement = connection.prepareStatement(query)) {
            statement.setInt(1, reservation.getId());
            return statement.executeUpdate();
        }
    }

    @Override
    public List<ReservationTrajet> showAll() throws SQLException {
        List<ReservationTrajet> reservations = new ArrayList<>();
        String query = "SELECT * FROM reservationtrajet";
        try (Statement statement = connection.createStatement();
             ResultSet resultSet = statement.executeQuery(query)) {
            while (resultSet.next()) {
                ReservationTrajet reservation = new ReservationTrajet(
                        resultSet.getInt("id"),
                        resultSet.getString("disponibilite"),
                        resultSet.getInt("vehicule_id"),
                        resultSet.getInt("trajet_id"),
                        resultSet.getInt("utilisateur_id")
                );
                reservations.add(reservation);
            }
        }
        return reservations;
    }

    // Récupérer les réservations par ID de véhicule et ID de trajet
    public List<ReservationTrajet> getReservationsByVehiculeAndTrajet(int vehiculeId, int trajetId) throws SQLException {
        List<ReservationTrajet> reservations = new ArrayList<>();
        String query = "SELECT * FROM reservationtrajet WHERE vehicule_id = ? AND trajet_id = ?";
        try (PreparedStatement statement = connection.prepareStatement(query)) {
            statement.setInt(1, vehiculeId);
            statement.setInt(2, trajetId);
            try (ResultSet resultSet = statement.executeQuery()) {
                while (resultSet.next()) {
                    ReservationTrajet reservation = new ReservationTrajet(
                            resultSet.getInt("id"),
                            resultSet.getString("disponibilite"),
                            resultSet.getInt("vehicule_id"),
                            resultSet.getInt("trajet_id"),
                            resultSet.getInt("utilisateur_id")
                    );
                    reservations.add(reservation);
                }
            }
        }
        return reservations;
    }

    // Vérifier si un véhicule existe
    private boolean vehiculeExists(int vehiculeId) throws SQLException {
        String query = "SELECT id FROM vehicule WHERE id = ?";
        try (PreparedStatement statement = connection.prepareStatement(query)) {
            statement.setInt(1, vehiculeId);
            try (ResultSet resultSet = statement.executeQuery()) {
                return resultSet.next(); // Retourne true si le véhicule existe
            }
        }
    }

    // Vérifier si un trajet existe
    private boolean trajetExists(int trajetId) throws SQLException {
        String query = "SELECT id FROM trajet WHERE id = ?";
        try (PreparedStatement statement = connection.prepareStatement(query)) {
            statement.setInt(1, trajetId);
            try (ResultSet resultSet = statement.executeQuery()) {
                return resultSet.next(); // Retourne true si le trajet existe
            }
        }
    }

    // Vérifier si un utilisateur existe
    private boolean userExists(int userId) throws SQLException {
        String query = "SELECT id FROM utilisateur WHERE id = ?";
        try (PreparedStatement statement = connection.prepareStatement(query)) {
            statement.setInt(1, userId);
            try (ResultSet resultSet = statement.executeQuery()) {
                return resultSet.next(); // Retourne true si l'utilisateur existe
            }
        }
    }
    public String getUsernameByUserId(int userId) throws SQLException {
        String query = "SELECT nom FROM utilisateur WHERE id = ?";
        try (PreparedStatement statement = connection.prepareStatement(query)) {
            statement.setInt(1, userId);
            try (ResultSet resultSet = statement.executeQuery()) {
                if (resultSet.next()) {
                    return resultSet.getString("nom");
                }
            }
        }
        return null; // Retourne null si l'utilisateur n'est pas trouvé
    }

    public Map<String, Integer> getReservationsByVehicleType(List<ReservationTrajet> reservations, List<Vehicule> vehicules) {
        Map<String, Integer> reservationsByType = new HashMap<>();

        for (ReservationTrajet reservation : reservations) {
            for (Vehicule vehicule : vehicules) {
                if (reservation.getVehiculeId() == vehicule.getId()) {
                    String type = vehicule.getType();
                    reservationsByType.put(type, reservationsByType.getOrDefault(type, 0) + 1);
                    break;
                }
            }
        }

        return reservationsByType;
    }
}
