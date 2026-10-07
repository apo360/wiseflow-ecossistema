package com.financas.finance_control.domain.model;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;
import java.math.BigDecimal;
import com.financas.finance_control.shared.enums.ScenarioType;

@Entity
@Table(name = "scenarios")
@Getter @Setter
public class Scenario extends BaseEntity {

    private String name;

    @Enumerated(EnumType.STRING)
    private ScenarioType type;

    private BigDecimal incomeReduction;

    private Boolean isActive = true;
}
