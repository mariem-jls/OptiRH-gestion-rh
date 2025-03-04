package tn.nexus.Entities;

import tn.nexus.Utils.Enums.Role;

public class User {

    private int id;
    private String nom;
    private String email;
    private String motDePasse;
    private Role role;
    private String address;

    public User() {
    }
    public User ( int id,String nom ){
        this.nom = nom;
        this.id = id;
    }



    public User(int id, String nom, String email, String motDePasse, Role role, String address) {
        this(nom, email, motDePasse, role, address);
        this.id = id;
    }

    public User(String nom, String email, String motDePasse, Role role, String address) {
        this.nom = nom;
        this.email = email;
        this.motDePasse = motDePasse;
        this.role = role;
        this.address = address;
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

    public String getEmail() {
        return email;
    }

    public void setEmail(String email) {
        this.email = email;
    }

    public String getMotDePasse() {
        return motDePasse;
    }

    public void setMotDePasse(String motDePasse) {
        this.motDePasse = motDePasse;
    }

    public Role getRole() {
        return role;
    }

    public void setRole(Role role) {
        this.role = role;
    }

    public String getAddress() {
        return address;
    }

    public void setAddress(String address) {
        this.address = address;
    }

    @Override
    public String toString() {
        return "User{" +
                "id=" + id +
                ", nom='" + nom + '\'' +
                ", email='" + email + '\'' +
                ", motDePasse='" + motDePasse + '\'' +
                ", role='" + role + '\'' +
                ", address='" + address + '\'' +
                '}';
    }
}
