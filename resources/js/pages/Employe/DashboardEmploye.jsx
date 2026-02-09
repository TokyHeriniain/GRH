import React, { useEffect, useState } from "react";
import { Card, Col, Row, Alert, Spinner, Badge } from "react-bootstrap";
import api from "../../axios";
import NavigationLayout from "../../components/NavigationLayout";

export default function EmployeDashboard() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [soldeGlobal, setSoldeGlobal] = useState(null);
  const [congesEnAttente, setCongesEnAttente] = useState(0);
  const [dernierConge, setDernierConge] = useState(null);

  useEffect(() => {
    fetchDashboard();
  }, []);

  const fetchDashboard = async () => {
    try {
      setLoading(true);

      const [
        soldeRes,
        mesCongesRes
      ] = await Promise.all([
        api.get("/api/employe/solde-global"),
        api.get("/api/employe/conges?page=1"),
      ]);

      setSoldeGlobal(soldeRes.data.solde_global_restant);

      const conges = mesCongesRes.data.data || [];

      setCongesEnAttente(
        conges.filter(c => c.status === "en_attente").length
      );

      setDernierConge(conges[0] || null);

    } catch (err) {
      setError("Impossible de charger le dashboard employé");
    } finally {
      setLoading(false);
    }
  };

  const soldeVariant =
    soldeGlobal <= 3 ? "danger" :
    soldeGlobal <= 10 ? "warning" :
    "success";

  return (
    <NavigationLayout>
      <h4 className="mb-4">📊 Tableau de bord employé</h4>

      {error && <Alert variant="danger">{error}</Alert>}

      {loading ? (
        <div className="text-center py-5">
          <Spinner animation="border" />
        </div>
      ) : (
        <Row className="g-4">

          {/* SOLDE GLOBAL */}
          <Col md={4}>
            <Card className="shadow-sm border-0 h-100">
              <Card.Body>
                <h6 className="text-muted">Solde total restant</h6>
                <h2 className={`text-${soldeVariant}`}>
                  {Number(soldeGlobal).toFixed(2)} jours
                </h2>
                <small className="text-muted">
                  Congé annuel & billets
                </small>
              </Card.Body>
            </Card>
          </Col>

          {/* CONGÉS EN ATTENTE */}
          <Col md={4}>
            <Card className="shadow-sm border-0 h-100">
              <Card.Body>
                <h6 className="text-muted">Demandes en attente</h6>
                <h2>{congesEnAttente}</h2>
                <small className="text-muted">
                  En attente de validation
                </small>
              </Card.Body>
            </Card>
          </Col>

          {/* DERNIÈRE DEMANDE */}
          <Col md={4}>
            <Card className="shadow-sm border-0 h-100">
              <Card.Body>
                <h6 className="text-muted">Dernière demande</h6>

                {dernierConge ? (
                  <>
                    <div className="fw-bold">
                      {dernierConge.leave_type?.nom}
                    </div>
                    <div className="text-muted small">
                      {dernierConge.date_debut} → {dernierConge.date_fin}
                    </div>
                    <Badge bg={
                      dernierConge.status === "approuve_rh" ? "success" :
                      dernierConge.status === "en_attente" ? "warning" :
                      "secondary"
                    }>
                      {dernierConge.status}
                    </Badge>
                  </>
                ) : (
                  <small className="text-muted">
                    Aucune demande enregistrée
                  </small>
                )}
              </Card.Body>
            </Card>
          </Col>

        </Row>
      )}
    </NavigationLayout>
  );
}
