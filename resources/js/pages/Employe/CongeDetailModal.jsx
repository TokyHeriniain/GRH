import React from "react";
import { Modal, Badge } from "react-bootstrap";

export default function CongeDetailModal({ show, onHide, conge }) {
  if (!conge) return null;

  const badgeVariant = {
    approuve: "success",
    approuve_rh: "success",
    en_attente: "warning",
    refuse: "danger",
    annule: "secondary",
  }[conge.status] || "secondary";

  return (
    <Modal show={show} onHide={onHide} centered>
      <Modal.Header closeButton>
        <Modal.Title>📄 Détail du congé</Modal.Title>
      </Modal.Header>

      <Modal.Body>
        <p><strong>Type :</strong> {conge.leave_type?.nom}</p>
        <p>
          <strong>Période :</strong>{" "}
          {conge.date_debut} → {conge.date_fin}
        </p>
        <p><strong>Jours :</strong> {conge.jours_utilises}</p>

        <p>
          <strong>Statut :</strong>{" "}
          <Badge bg={badgeVariant}>
            {conge.status.replace("_", " ")}
          </Badge>
        </p>

        {conge.raison && (
          <p>
            <strong>Raison :</strong><br />
            {conge.raison}
          </p>
        )}
      </Modal.Body>
    </Modal>
  );
}
