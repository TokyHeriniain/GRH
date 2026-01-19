import React from "react";
import { Modal, Badge, Row, Col, Card } from "react-bootstrap";
import dayjs from "dayjs";

const fmt = (v) =>
  v !== null && v !== undefined ? Number(v).toFixed(2) : "--";

export default function CongeDetailModal({ show, onHide, leave }) {
  if (!leave) return null;

  return (
    <Modal show={show} onHide={onHide} size="lg" centered>
      <Modal.Header closeButton>
        <Modal.Title>📄 Détail du congé</Modal.Title>
      </Modal.Header>

      <Modal.Body>
        {/* ================= PERSONNEL ================= */}
        <Card className="mb-3 shadow-sm">
          <Card.Header className="fw-bold bg-light">
            👤 Informations du personnel
          </Card.Header>
          <Card.Body>
            <Row>
              <Col md={4}><strong>Matricule :</strong></Col>
              <Col md={8}>{leave.personnel?.matricule}</Col>
            </Row>
            <Row>
              <Col md={4}><strong>Nom :</strong></Col>
              <Col md={8}>{leave.personnel?.nom}</Col>
            </Row>
            <Row>
              <Col md={4}><strong>Prénom :</strong></Col>
              <Col md={8}>{leave.personnel?.prenom}</Col>
            </Row>
            <Row>
              <Col md={4}><strong>Fonction :</strong></Col>
              <Col md={8}>{leave.personnel?.fonction?.nom || "—"}</Col>
            </Row>
          </Card.Body>
        </Card>

        {/* ================= CONGÉ ================= */}
        <Card className="mb-3 shadow-sm">
          <Card.Header className="fw-bold bg-light">
            🗓️ Informations du congé
          </Card.Header>
          <Card.Body>
            <Row>
              <Col md={4}><strong>Type :</strong></Col>
              <Col md={8}>{leave.leave_type?.nom}</Col>
            </Row>
            <Row>
              <Col md={4}><strong>Période :</strong></Col>
              <Col md={8}>
                {dayjs(leave.date_debut).format("DD/MM/YYYY")}{" "}
                {leave.heure_debut} →{" "}
                {dayjs(leave.date_fin).format("DD/MM/YYYY")}{" "}
                {leave.heure_fin}
              </Col>
            </Row>
            <Row>
              <Col md={4}><strong>Raison :</strong></Col>
              <Col md={8}>{leave.raison || "—"}</Col>
            </Row>
            <Row className="mt-2">
              <Col md={4}><strong>Statut :</strong></Col>
              <Col md={8}>
                <Badge
                  bg={
                    leave.status === "approuve_rh"
                      ? "success"
                      : leave.status === "rejete"
                      ? "danger"
                      : "warning"
                  }
                >
                  {leave.status.replace("_", " ")}
                </Badge>
              </Col>
            </Row>
          </Card.Body>
        </Card>

        {/* ================= SOLDES ================= */}
        <Card className="shadow-sm">
          <Card.Header className="fw-bold bg-light">
            📊 Soldes de congé
          </Card.Header>
          <Card.Body>
            <Row>
              <Col md={4}><strong>Droit total :</strong></Col>
              <Col md={8}>
                <Badge bg="info">{fmt(leave.droit_total)} jours</Badge>
              </Col>
            </Row>
            <Row>
              <Col md={4}><strong>Jours utilisés :</strong></Col>
              <Col md={8}>
                <Badge bg="warning" text="dark">
                  − {fmt(leave.jours_utilises)} jours
                </Badge>
              </Col>
            </Row>
            <Row>
              <Col md={4}><strong>Solde restant :</strong></Col>
              <Col md={8}>
                <Badge bg="success">
                  {fmt(leave.solde_restant)} jours
                </Badge>
              </Col>
            </Row>
          </Card.Body>
        </Card>
      </Modal.Body>
    </Modal>
  );
}
