import React, { useEffect, useState } from "react";
import axios from "axios";
import { Row, Col, Card, Spinner } from "react-bootstrap";
import NavigationLayout from "../../components/NavigationLayout";

export default function DashboardRH() {
  const [data, setData] = useState({
    personnels: { total: 0 },
    conges: {
      total: 0,
      approuves: 0,
      refuses: 0,
      en_attente: 0,
    },
    cloture: { annees_cloturees: 0 },
    pertes: { total_jours_perdus: 0 },
  });

  const [loading, setLoading] = useState(true);

  useEffect(() => {
    axios
      .get("/api/rh/dashboard")
      .then((res) => setData(res.data))
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <NavigationLayout>
        <div className="text-center my-5">
          <Spinner animation="border" />
        </div>
      </NavigationLayout>
    );
  }

  return (
    <NavigationLayout>
      <h4 className="mb-4">📊 Dashboard RH</h4>

      <Row className="g-3">
        <Col md={3}>
          <Card className="shadow-sm">
            <Card.Body>
              <Card.Title>👥 Personnels</Card.Title>
              <h2>{data.personnels.total}</h2>
            </Card.Body>
          </Card>
        </Col>

        <Col md={3}>
          <Card className="shadow-sm">
            <Card.Body>
              <Card.Title>📄 Congés</Card.Title>
              <div>Total : {data.conges.total}</div>
              <div>✔️ Approuvés : {data.conges.approuves}</div>
              <div>⏳ En attente : {data.conges.en_attente}</div>
              <div>❌ Refusés : {data.conges.refuses}</div>
            </Card.Body>
          </Card>
        </Col>

        <Col md={3}>
          <Card className="shadow-sm">
            <Card.Body>
              <Card.Title>🗓️ Clôtures</Card.Title>
              <h2>{data.cloture.annees_cloturees}</h2>
            </Card.Body>
          </Card>
        </Col>

        <Col md={3}>
          <Card className="shadow-sm">
            <Card.Body>
              <Card.Title>⚠️ Jours perdus</Card.Title>
              <h2>{Number(data.pertes.total_jours_perdus).toFixed(2)}</h2>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </NavigationLayout>
  );
}
