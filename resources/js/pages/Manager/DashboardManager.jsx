import { useEffect, useState } from "react";
import api from "@/axios";
import { Card, Row, Col, Button } from "react-bootstrap";
import { useNavigate } from "react-router-dom";
import NavigationLayout from "../../components/NavigationLayout";

const DashboardManager = () => {
  const [stats, setStats] = useState({
    en_attente: 0,
    approuve_manager: 0,
    approuve_rh: 0,
    rejete: 0
  });

  const navigate = useNavigate();

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    const res = await api.get("/api/manager/conges");
    const leaves = res.data.data;

    const grouped = leaves.reduce((acc, leave) => {
      acc[leave.status] = (acc[leave.status] || 0) + 1;
      return acc;
    }, {});

    setStats(grouped);
  };

  const cardStyle = {
    cursor: "pointer",
    transition: "0.3s",
  };

  return (
    <NavigationLayout>
        <div>
      <h3 className="mb-4">Dashboard Manager</h3>

      <Row>
        <Col md={3}>
          <Card style={cardStyle} onClick={() => navigate("/manager/conges")}>
            <Card.Body>
              <h5>En attente</h5>
              <h2>{stats.en_attente || 0}</h2>
            </Card.Body>
          </Card>
        </Col>

        <Col md={3}>
          <Card>
            <Card.Body>
              <h5>Validé Manager</h5>
              <h2>{stats.approuve_manager || 0}</h2>
            </Card.Body>
          </Card>
        </Col>

        <Col md={3}>
          <Card>
            <Card.Body>
              <h5>Validé RH</h5>
              <h2>{stats.approuve_rh || 0}</h2>
            </Card.Body>
          </Card>
        </Col>

        <Col md={3}>
          <Card>
            <Card.Body>
              <h5>Rejeté</h5>
              <h2>{stats.rejete || 0}</h2>
            </Card.Body>
          </Card>
        </Col>
      </Row>

      <div className="mt-4">
        <Button onClick={() => navigate("/manager/conges")}>
          Voir les demandes équipe
        </Button>
      </div>
    </div>
    </NavigationLayout>    
  );
};

export default DashboardManager;
