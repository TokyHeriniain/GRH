import React, { useEffect, useState } from "react";
import { Card, Col, Row, Alert, Spinner, Badge } from "react-bootstrap";
import api from "../../axios";
import NavigationLayout from "../../components/NavigationLayout";
import EmployeCongeCalendar from "./EmployeCongeCalendar";

export default function EmployeDashboard() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const [soldeGlobal, setSoldeGlobal] = useState(0);
  const [congesEnAttente, setCongesEnAttente] = useState(0);
  const [dernierConge, setDernierConge] = useState(null);
  const [demandesMois, setDemandesMois] = useState(0);

  useEffect(() => {
    fetchDashboard();
  }, []);

  const fetchDashboard = async () => {
    try {
      setLoading(true);

      const [soldeRes, mesCongesRes] = await Promise.all([
        api.get("/api/employe/solde-global"),
        api.get("/api/employe/conges?page=1"),
      ]);

      const solde = Number(soldeRes.data.solde_global_restant ?? 0);
      setSoldeGlobal(solde);

      const conges = mesCongesRes.data.data || [];

      setCongesEnAttente(
        conges.filter((c) => c.status === "en_attente").length
      );

      setDernierConge(conges[0] || null);

      const currentMonth = new Date().toISOString().slice(0, 7);
      setDemandesMois(
        conges.filter((c) => c.date_debut?.startsWith(currentMonth)).length
      );

    } catch (err) {
      setError("Impossible de charger le dashboard employé");
    } finally {
      setLoading(false);
    }
  };

  const renderStatusLabel = (status) => {
    switch (status) {
      case "approuve_manager":
        return "Approuvée manager";
      case "approuve_rh":
        return "Approuvée RH";
      case "en_attente":
        return "En attente";
      case "rejete":
        return "Rejetée";
      case "annule":
        return "Annulée";
      default:
        return status;
    }
  };

  const soldeVariant =
    soldeGlobal <= 3
      ? "danger"
      : soldeGlobal <= 10
      ? "warning"
      : "success";

  return (
    <NavigationLayout>
      <h4 className="mb-4">📊 Tableau de bord employé</h4>

      {error && <Alert variant="danger">{error}</Alert>}

      {loading ? (
        <div className="text-center py-5">
          <Spinner animation="border" />
        </div>
      ) : (
        <>
          <Row className="g-4">

            {/* SOLDE GLOBAL */}
            <Col md={4}>
              <Card className="shadow-sm border-0 h-100">
                <Card.Body>
                  <h6 className="text-muted">Solde total restant</h6>

                  <h2 className={`text-${soldeVariant}`}>
                    {soldeGlobal.toFixed(2)} jours
                  </h2>

                  <small className="text-muted d-block">
                    Congé annuel & billets — {new Date().getFullYear()}
                  </small>

                  {soldeGlobal <= 3 && (
                    <Alert variant="danger" className="mt-2 p-2">
                      ⚠ Solde faible, pensez à planifier vos congés
                    </Alert>
                  )}
                </Card.Body>
              </Card>
            </Col>

            {/* CONGÉS EN ATTENTE */}
            <Col md={4}>
              <Card className="shadow-sm border-0 h-100">
                <Card.Body>
                  <h6 className="text-muted">Demandes en attente</h6>

                  <h2>{congesEnAttente}</h2>

                  <small className="text-muted d-block">
                    En attente de validation
                  </small>

                  {congesEnAttente > 0 && (
                    <a href="/employe/conges" className="small">
                      Voir les demandes →
                    </a>
                  )}
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

                      <Badge
                        bg={
                          dernierConge.status === "approuve_rh"
                            ? "success"
                            : dernierConge.status === "en_attente"
                            ? "warning"
                            : "secondary"
                        }
                        className="mt-2"
                      >
                        {renderStatusLabel(dernierConge.status)}
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

            {/* ACTIVITÉ DU MOIS */}
            <Col md={12}>
              <Card className="shadow-sm border-0">
                <Card.Body>
                  <h6 className="text-muted">📈 Activité ce mois</h6>
                  <h4>{demandesMois} demande(s) envoyée(s)</h4>
                </Card.Body>
              </Card>
            </Col>
          </Row>

          {/* CALENDRIER */}
          <Card className="mt-4 shadow-sm border-0">
            <Card.Body>
              <h5 className="mb-3">🗓️ Vue calendrier des congés</h5>
              <EmployeCongeCalendar />
            </Card.Body>
          </Card>
        </>
      )}
    </NavigationLayout>
  );
}
