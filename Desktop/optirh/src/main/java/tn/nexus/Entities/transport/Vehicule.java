package tn.nexus.Entities.transport;

public class Vehicule {

    private int id;
    private String disponibilite;
    private String type;
    private int nbrplace;
    private int trajetId;
    private int nbrReservation;


    public Vehicule() {
    }

    public Vehicule(int id, String disponibilite, String type, int nbrplace, int trajetId, int nbrReservation) {
        this.id = id;
        this.disponibilite = disponibilite;
        this.type = type;
        this.nbrplace = nbrplace;
        this.trajetId = trajetId;
        this.nbrReservation = 0;

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

    public String getType() {
        return type;
    }

    public void setType(String type) {
        this.type = type;
    }

    public int getNbrplace() {
        return nbrplace;
    }

    public void setNbrplace(int nbrplace) {
        this.nbrplace = nbrplace;
    }
    public int getTrajetId() {
        return trajetId;
    }

    public void setTrajetId(int trajetId) {
        this.trajetId = trajetId;
    }

    public int getNbrReservation() {
        return nbrReservation;
    }

    public void setNbrReservation(int nbrReservation) {
        this.nbrReservation = nbrReservation;
    }

    @Override
    public String toString() {
        return "Reservation_Evenement{" +
                "id=" + id +
                ", disponibilite=" + disponibilite +
                ", type=" + type +
                ", nbrplace=" + nbrplace +
                ", trajetId=" + trajetId +
                '}';
    }
}