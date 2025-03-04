package tn.nexus.Entities.transport;

public class Statistique {

    private int id;
    private String nom;
    private int nombreReservations;

    public Statistique(int id, String nom, int nombreReservations) {
        this.id = id;
        this.nom = nom;
        this.nombreReservations = nombreReservations;
    }

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getNom() {
        return nom;
    }

    public void setNom(String nom) {
        this.nom = nom;
    }

    public int getNombreReservations() {
        return nombreReservations;
    }

    public void setNombreReservations(int nombreReservations) {
        this.nombreReservations = nombreReservations;
    }

    @Override
    public String toString() {
        return "Statistique{" +
                "id=" + id +
                ", nom='" + nom + '\'' +
                ", nombreReservations=" + nombreReservations +
                '}';
    }
}