import { useEffect, useState } from "react";
import api from "@/axios";
import { Table, Button, Badge, Form, Row, Col } from "react-bootstrap";
import NavigationLayout from "../../components/NavigationLayout";

const CongeEquipe = () => {
  const [leaves, setLeaves] = useState([]);
  const [statusFilter, setStatusFilter] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchLeaves();
  }, [statusFilter]);

  const fetchLeaves = async () => {
    setLoading(true);
    try {
      const res = await api.get("/api/manager/conges", {
        params: { status: statusFilter }
      });
      setLeaves(res.data.data);
    } catch (err) {
      console.error(err);
    }
    setLoading(false);
  };

  const approve = async (id) => {
    await api.post(`/api/manager/conges/${id}/approve`);
    fetchLeaves();
  };

  const reject = async (id) => {
    const reason = prompt("Motif du rejet ?");
    if (!reason) return;

    await api.post(`/api/manager/conges/${id}/reject`, {
      rejection_reason: reason
    });

    fetchLeaves();
  };

  const badgeColor = (status) => {
    switch (status) {
      case "en_attente":
        return "warning";
      case "approuve_manager":
        return "info";
      case "approuve_rh":
        return "success";
      case "rejete":
        return "danger";
      default:
        return "secondary";
    }
  };

  return (
    <NavigationLayout>
        <div>
      <h4 className="mb-4">Demandes de congé de mon équipe</h4>

      <Row className="mb-3">
        <Col md={4}>
          <Form.Select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="">Tous les statuts</option>
            <option value="en_attente">En attente</option>
            <option value="approuve_manager">Validé Manager</option>
            <option value="approuve_rh">Validé RH</option>
            <option value="rejete">Rejeté</option>
          </Form.Select>
        </Col>
      </Row>

      <Table striped bordered hover responsive>
        <thead>
          <tr>
            <th>Employé</th>
            <th>Type</th>
            <th>Du</th>
            <th>Au</th>
            <th>Jours</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {leaves.map((leave) => (
            <tr key={leave.id}>
              <td>
                {leave.personnel.nom} {leave.personnel.prenom}
              </td>
              <td>{leave.leave_type.nom}</td>
              <td>{leave.date_debut}</td>
              <td>{leave.date_fin}</td>
              <td>{leave.jours_utilises}</td>
              <td>
                <Badge bg={badgeColor(leave.status)}>
                  {leave.status}
                </Badge>
              </td>
              <td>
                {leave.status === "en_attente" && (
                  <>
                    <Button
                      size="sm"
                      variant="success"
                      className="me-2"
                      onClick={() => approve(leave.id)}
                    >
                      Valider
                    </Button>
                    <Button
                      size="sm"
                      variant="danger"
                      onClick={() => reject(leave.id)}
                    >
                      Rejeter
                    </Button>
                  </>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </Table>

      {loading && <p>Chargement...</p>}
    </div>
    </NavigationLayout>
    
  );
};

export default CongeEquipe;
