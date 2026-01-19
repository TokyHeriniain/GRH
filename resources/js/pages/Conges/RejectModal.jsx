import React, { useState, useEffect } from "react";
import { Button, Form, Modal, Alert } from "react-bootstrap";

export default function RejectModal({
  show,
  onHide,
  reason,
  setReason,
  confirmReject,
}) {
  const [submitting, setSubmitting] = useState(false);
  const isInvalid = !reason || reason.trim().length < 5;

  useEffect(() => {
    if (!show) {
      setSubmitting(false);
    }
  }, [show]);

  const handleReject = async () => {
    if (isInvalid || submitting) return;

    try {
      setSubmitting(true);
      await confirmReject();
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Modal show={show} onHide={onHide} centered backdrop="static">
      <Modal.Header closeButton>
        <Modal.Title>❌ Rejet du congé (RH)</Modal.Title>
      </Modal.Header>

      <Modal.Body>
        <Form.Group>
          <Form.Label>Motif du rejet <span className="text-danger">*</span></Form.Label>
          <Form.Control
            as="textarea"
            rows={4}
            placeholder="Expliquez clairement le motif du rejet RH..."
            value={reason}
            onChange={(e) => setReason(e.target.value)}
            isInvalid={isInvalid && reason !== ""}
          />
          <Form.Control.Feedback type="invalid">
            Le motif est obligatoire (minimum 5 caractères).
          </Form.Control.Feedback>
        </Form.Group>

        <Alert variant="warning" className="mt-3 mb-0">
          ⚠️ Cette action est définitive et sera enregistrée dans l’historique RH.
        </Alert>
      </Modal.Body>

      <Modal.Footer>
        <Button variant="secondary" onClick={onHide} disabled={submitting}>
          Annuler
        </Button>

        <Button
          variant="danger"
          onClick={handleReject}
          disabled={isInvalid || submitting}
        >
          {submitting ? "Rejet en cours..." : "Confirmer le rejet"}
        </Button>
      </Modal.Footer>
    </Modal>
  );
}
