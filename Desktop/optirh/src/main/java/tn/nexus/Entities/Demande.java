package tn.nexus.Entities;

import java.time.LocalDate;

public class Demande {
    
private Integer id;
private String status;
private LocalDate date;
private String description;
private Integer utilistaeurId;

public Demande() {
}

public Demande(Integer id, String status, LocalDate date, String description, Integer utilistaeurId) {
    this.id = id;
    this.status = status;
    this.date = date;
    this.description = description;
    this.utilistaeurId = utilistaeurId;

}

public Integer getId() {
    return id;
}

public void setId(Integer id) {
    this.id = id;
}

public String getStatus() {
    return status;
}

public void setStatus(String status) {
    this.status = status;
}

public LocalDate getDate() {
    return date;
}

public void setDate(LocalDate date) {
    this.date = date;
}

public String getDescription() {
    return description;
}

public void setDescription(String description) {
    this.description = description;
}

public Integer getUtilistaeurId() {
    return utilistaeurId;
}

public void setUtilistaeurId(Integer utilistaeurId) {
    this.utilistaeurId = utilistaeurId;
}

@Override
public String toString() {
    return "User{" +
            "id=" + id +
            ", status='" + status + '\'' +
            ", date='" + date + '\'' +
            ", description='" + description + '\'' +
            ", utilistaeurId=" + utilistaeurId +
            '}';
}


}
