package tn.nexus.Entities.transport;

import javafx.beans.property.IntegerProperty;
import javafx.beans.property.SimpleIntegerProperty;
import javafx.beans.property.SimpleStringProperty;
import javafx.beans.property.StringProperty;

public class Vehicule {

    private int id;
    private String disponibilite;
    private String type;
    private int nbrplace;
    private int trajetId;


    public Vehicule() {
    }

    public Vehicule(int id, String disponibilite, String type, int nbrplace, int trajetId) {
        this.id = id;
        this.disponibilite = disponibilite;
        this.type = type;
        this.nbrplace = nbrplace;
        this.trajetId = trajetId;

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



    @Override
    public String toString() {
        return "Reservation_Evenement{" +
                "id=" + id +
                ", disponibilite=" + disponibilite +
                ", type=" + type +
                ", nbrplace=" + nbrplace +
                '}';
    }
}