package tn.nexus.services;

import java.sql.*;
import java.util.ArrayList;
import java.util.List;

import tn.nexus.Entities.Demande;
import tn.nexus.Utils.DBConnection;

public class DemandeService implements CRUD<Demande>{

    private Connection cnx = DBConnection.getInstance().getCnx();
    private Statement st ;
    private PreparedStatement ps ;

    @Override
    public int insert(Demande demande) throws SQLException {
        String req = "INSERT INTO `demande`(`id`, `status`, `date`, `description`, `utilisateur_id`) VALUES (?, ?, ?)";

        ps = cnx.prepareStatement(req);

        ps.setString(1, demande.getStatus());
        ps.setString(2, demande.getDate());
        ps.setInt(3, demande.getDescription());

        return ps.executeUpdate();    }

    @Override
    public int update(Demande t) throws SQLException {
        // TODO Auto-generated method stub
        throw new UnsupportedOperationException("Unimplemented method 'update'");
    }

    @Override
    public int delete(Demande t) throws SQLException {
        // TODO Auto-generated method stub
        throw new UnsupportedOperationException("Unimplemented method 'delete'");
    }

    @Override
    public List<Demande> showAll() throws SQLException {
        // TODO Auto-generated method stub
        throw new UnsupportedOperationException("Unimplemented method 'showAll'");
    }

}
