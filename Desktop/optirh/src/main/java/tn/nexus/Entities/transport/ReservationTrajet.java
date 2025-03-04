package tn.nexus.Entities.transport;

public class ReservationTrajet {
    private int id;
    private String disponibilite;
    private int vehiculeId;
    private int trajetId;
    private int userId;


    public ReservationTrajet() {
    }

    public ReservationTrajet(int id, String disponibilite, int vehiculeId, int trajetId, int userId) {
        this.id = id;
        this.disponibilite = disponibilite;
        this.vehiculeId = vehiculeId;
        this.trajetId = trajetId;
        this.userId = userId;
    }

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getDisponibilite() {
        return disponibilite;
    }

    public void setDisponibilite(String disponibilite) {
        this.disponibilite = disponibilite;
    }

    public int getVehiculeId() {
        return vehiculeId;
    }

    public void setVehiculeId(int vehiculeId) {
        this.vehiculeId = vehiculeId;
    }

    public int getTrajetId() {
        return trajetId;
    }

    public void setTrajetId(int trajetId) {
        this.trajetId = trajetId;
    }

    public int getUserId() {
        return userId;
    }


    public void setUserId(int userId) {
        this.userId = userId;
    }


    @Override
    public String toString() {
        return "Reservation_Evenement{" +
                "id=" + id +
                ", disponibilite=" + disponibilite + '\'' +
                ", vehiculeId=" + vehiculeId +
                ", trajetId=" + trajetId +
                ", userId=" + userId +
                '}';
    }
}