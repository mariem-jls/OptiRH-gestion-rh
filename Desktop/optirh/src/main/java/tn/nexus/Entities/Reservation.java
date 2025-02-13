package tn.nexus.Entities;

public class Reservation {
    private int id;
    private String disponibilite;

    public Reservation() {
    }

    public Reservation(int id, String disponibilite) {
        this.id = id;
        this.disponibilite = disponibilite;
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

    @Override
    public String toString() {
        return "Reservation_Evenement{" +
                "id=" + id +
                ", disponibilite=" + disponibilite +
                '}';
    }
}