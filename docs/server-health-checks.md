# RoadRunner Health Checks
Ifg you are using RoadRunner for your server you have access to a health check endpoint to get health information of
Road Runner:

```
http://localhost:2114/health?plugin=http
```

These checks can be used with:
- Kubernetes readiness and liveness probes
- AWS target group health checks
- GCE Load Balancing health checks