package com.financas.finance_control.domain.model;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;
import com.financas.finance_control.shared.enums.AlertCategory;
import com.financas.finance_control.shared.enums.AlertType;
import java.time.LocalDateTime;

@Entity
@Table(name = "alerts")
@Getter @Setter
public class Alert extends BaseEntity {

    @Column(name = "user_id")
    private Long userId;

    private String title;

    @Column(columnDefinition = "TEXT")
    private String message;

    @Enumerated(EnumType.STRING)
    private AlertType type;

    @Enumerated(EnumType.STRING)
    private AlertCategory category;

    private Boolean isRead = false;
    private Boolean isResolved = false;

    private LocalDateTime readAt;
    private LocalDateTime resolvedAt;
}
