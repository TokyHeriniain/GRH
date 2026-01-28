import React, { useEffect, useState } from "react";
import axios from "axios";
import {
  Card,
  Form,
  Button,
  Spinner,
  Row,
  Col,
  Badge,
} from "react-bootstrap";
import { toast } from "react-toastify";
import NavigationLayout from "../../components/NavigationLayout";

export default function RolePermissions() {
  const [roles, setRoles] = useState([]);
  const [selectedRole, setSelectedRole] = useState(null);
  const [permissions, setPermissions] = useState({});
  const [checked, setChecked] = useState([]);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    axios.get("/api/admin/roles").then((res) => setRoles(res.data));
  }, []);

  const loadPermissions = async (roleId) => {
    setLoading(true);
    try {
      const res = await axios.get(`/api/admin/roles/${roleId}/permissions`);
      setPermissions(res.data.permissions);
      setChecked(res.data.assigned);
      setSelectedRole(res.data.role);
    } catch {
      toast.error("Erreur chargement permissions");
    } finally {
      setLoading(false);
    }
  };

  const togglePermission = (id) => {
    setChecked((prev) =>
      prev.includes(id) ? prev.filter((p) => p !== id) : [...prev, id]
    );
  };

  const save = async () => {
    try {
      await axios.put(`/api/admin/roles/${selectedRole.id}/permissions`, {
        permissions: checked,
      });
      toast.success("Permissions mises à jour");
    } catch {
      toast.error("Erreur sauvegarde permissions");
    }
  };

  return (
    <NavigationLayout>
      <Card className="p-4 shadow-sm border-0">
        <h4>🔐 Permissions par rôle</h4>

        <Form.Select
          className="my-3"
          onChange={(e) => loadPermissions(e.target.value)}
          defaultValue=""
        >
          <option value="" disabled>
            Sélectionner un rôle
          </option>
          {roles.map((r) => (
            <option key={r.id} value={r.id}>
              {r.name}
            </option>
          ))}
        </Form.Select>

        {loading && <Spinner />}

        {!loading && selectedRole && (
          <>
            {Object.keys(permissions).map((module) => (
              <Card key={module} className="mb-3">
                <Card.Header className="fw-bold">
                  📦 {module.toUpperCase()}
                </Card.Header>
                <Card.Body>
                  <Row>
                    {permissions[module].map((p) => (
                      <Col md={4} key={p.id}>
                        <Form.Check
                          type="checkbox"
                          label={
                            <>
                              {p.name}{" "}
                              <Badge bg="secondary">{p.action}</Badge>
                            </>
                          }
                          checked={checked.includes(p.id)}
                          onChange={() => togglePermission(p.id)}
                        />
                      </Col>
                    ))}
                  </Row>
                </Card.Body>
              </Card>
            ))}

            <Button onClick={save} className="mt-2">
              💾 Enregistrer
            </Button>
          </>
        )}
      </Card>
    </NavigationLayout>
  );
}